<?php

namespace App\Controller;

use App\Entity\BlockedEmailAddress;
use App\Entity\RegistrationQuestion;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\RegistrationQuestionRepository;
use App\Repository\UserRepository;
use App\Utilities\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    private UserRepository $userRepository;

    private RegistrationQuestionRepository $registrationQuestionRepository;

    public function __construct(private EntityManagerInterface $entityManager, private Mailer $mailer)
    {
        $this->userRepository = $entityManager->getRepository(User::class);
        $this->registrationQuestionRepository = $entityManager->getRepository(RegistrationQuestion::class);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastEmailAddress = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_email_address' => $lastEmailAddress,
            // Unsure how to better deal with this. It would be nice to be able to set that last authentication error
            'email_verification_failed' => $request->get('email_verification_failed', false),
            'error' => $error,
        ]);
    }

    #[Route(path: '/signup', name: 'app_signup')]
    public function signup(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        Mailer $mailer,
        UrlGeneratorInterface $router,
    ): Response {
        if (0 === $this->userRepository->count()) {
            return $this->redirectToRoute('app_index_index');
        }

        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $registrationSetting = $settingsRepository->getSettingByName('registration');
        if (empty($registrationSetting) || 'on' !== $registrationSetting->getValue()) {
            $this->addFlash('warning', 'Registrations are currently disabled');

            return $this->redirectToRoute('app_index_index');
        }

        $newRegistrationQuestion = $this->registrationQuestionRepository->getOneRandom();

        if ('POST' !== $request->getMethod()) {
            return $this->render('security/signup.html.twig', [
                'question' => $newRegistrationQuestion,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('signup', $submittedToken)) {
            $this->addFlash('warning', 'Something went wrong, please try again.');

            return $this->render('security/signup.html.twig');
        }

        $fieldErrors = $this->validateSignUp($request);

        if (!empty($fieldErrors)) {
            return $this->render('security/signup.html.twig', [
                'errors' => $fieldErrors,
                'question' => $newRegistrationQuestion,
                'values' => [
                    'username' => trim($request->get('username')),
                    'email' => trim($request->get('email')),
                ],
            ]);
        }

        $emailVerificationString = bin2hex(random_bytes(16));

        // We handle existing email address validation here, and not in the validator to give the same end user
        // experience so as to prevent email address harvesting.

        $existingEmailAddress = $this->userRepository->findOneBy([
            'email_address' => trim($request->get('email')),
        ]);

        $blockedEmailAddressRepository = $this->entityManager->getRepository(BlockedEmailAddress::class);
        $blockedEmailAddress = $blockedEmailAddressRepository->findOneBy([
            'email_address' => trim($request->get('email')),
        ]);

        // Do not attempt to invert this to reduce indentation, we want the same flash message (and any other behaviour)
        // we add at the end.
        if (empty($existingEmailAddress) && empty($blockedEmailAddress)) {
            $user = new User();
            $user
                ->setUsername(trim($request->get('username')))
                ->setCreated(new \DateTimeImmutable())
                ->setEmailAddress(trim($request->get('email')))
                ->setEmailVerificationString($emailVerificationString)
            ;

            $hashedPassword = $passwordHasher->hashPassword($user, $request->get('password'));

            $user->setPassword($hashedPassword);

            $entityErrors = $validator->validate($user);
            if (count($entityErrors) > 0) {
                $this->addFlash('warning', 'Something went wrong with your details, please try again.');

                return $this->render('signup.html.twig', [
                    'question' => $newRegistrationQuestion,
                ]);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $domain = getenv('HABITAT_DOMAIN');

            $mailer->send(
                $user->getEmailAddress(),
                $settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue(),
                'Verify your email address for ' . $domain,
                '<p>Hello ' . $user->getUsername() . ',</p>' .
                '<p>Click the link below to verify the email address for your account.</p>' .
                '<p>Ignore this email if you didn\'t create this account.</p>' .
                '<p><a href="https://' . $domain . $router->generate('app_verify_user', [
                    'userId' => $user->getId(),
                    'verificationString' => $emailVerificationString,
                ]) . '">Verify your email address</a>'
            );
        }

        $this->addFlash('notice', 'Check your emails to verify your email address');

        return $this->redirectToRoute('app_index_index');
    }

    private function validateSignUp(Request $request): array
    {
        $errors = [];

        if (empty(trim($request->get('username'))) || mb_strlen(trim($request->get('username'))) < User::USERNAME_MIN_LENGTH) {
            $errors['username'][] = 'Your username must be a minimum of ' . User::USERNAME_MIN_LENGTH . ' characters';
        }

        if (mb_strlen(trim($request->get('username'))) > User::USERNAME_MAX_LENGTH) {
            $errors['username'][] = 'Your username must be a maximum of ' . User::USERNAME_MAX_LENGTH . ' characters';
        }

        if (!empty(trim($request->get('username')) && !ctype_alnum(trim($request->get('username'))))) {
            $errors['username'][] = 'Your username must only use alphabetic and numeric characters';
        }

        $existingUser = $this->userRepository->findOneBy([
            'username' => trim($request->get('username')),
        ]);

        if ($existingUser) {
            $errors['username'][] = 'This username is already taken';
        }

        if (empty(trim($request->get('email'))) || !filter_var(trim($request->get('email')), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'This is not a valid email address';
        }

        if (!$this->isPasswordStrong($request->get('password'))) {
            $errors['password'][] = 'You must use a stronger password';
        }

        if ($this->registrationQuestionRepository->count() > 0) {
            $question = $this->registrationQuestionRepository->findOneBy([
                'id' => (int) $request->get('question'),
            ]);

            if (empty($question)) {
                $errors['question'][] = 'Please try answering this again';
            } elseif ('' === $request->get('answer')) {
                $errors['question'][] = 'You must answer the registration challenge question';
            } elseif (!$this->questionHasAnswer($question, $request->get('answer'))) {
                $errors['question'][] = 'The answer you gave is incorrect. Please try again';
            }
        }

        return $errors;
    }

    private function questionHasAnswer(RegistrationQuestion $question, string $submittedAnswer): bool
    {
        $correctAnswers = [];
        foreach ($question->getAnswers() as $answer) {
            $correctAnswers[] = trim(mb_strtolower($answer->getAnswer()));
        }

        if (!in_array(trim(mb_strtolower($submittedAnswer)), $correctAnswers)) {
            return false;
        }

        return true;
    }

    private function isPasswordStrong($password): bool
    {
        if (empty($password)) {
            return false;
        }

        if (mb_strlen($password) < User::PASSWORD_MIN_LENGTH) {
            return false;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    #[Route(path: '/verify/{userId}/{verificationString}', name: 'app_verify_user')]
    public function verifyUser(
        int $userId,
        string $verificationString,
        Security $security,
    ): Response {
        $user = $this->userRepository->findOneBy([
            'id' => $userId,
            'email_verification_string' => $verificationString,
        ]);

        if (empty($verificationString) || 32 !== strlen($verificationString) || !$user) {
            $this->addFlash('warning', 'Account verification failed.');

            return $this->redirectToRoute('app_index_index');
        }

        $user->setEmailVerified(true);
        $user->setEmailVerificationString(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $security->login($user, 'security.authenticator.form_login.main');

        $this->addFlash('notice', 'Your account has been verified');

        return $this->redirectToRoute('app_index_index');
    }

    #[Route(path: '/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        UrlGeneratorInterface $router,
        Mailer $mailer,
        Request $request,
    ): Response {
        if ('POST' !== $request->getMethod()) {
            return $this->render('security/forgot_password.html.twig');
        }

        $emailAddress = trim($request->get('email'));

        $user = $this->userRepository->findOneBy([
            'email_address' => $emailAddress,
        ]);

        if ($user) {
            $emailVerificationString = bin2hex(random_bytes(16));
            $user->setEmailVerificationString($emailVerificationString);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $settingsRepository = $this->entityManager->getRepository(Settings::class);
            $domain = getenv('HABITAT_DOMAIN');

            $mailer->send(
                $user->getEmailAddress(),
                $settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue(),
                'Reset your password for ' . $domain,
                '<p>Hello ' . $user->getUsername() . ',</p>' .
                '<p>Click the link below to reset the password for your account.</p>' .
                '<p>Ignore this email if you didn\'t request a password reset.</p>' .
                '<p><a href="https://' . $domain . $router->generate('app_reset_password', [
                    'userId' => $user->getId(),
                    'verificationString' => $emailVerificationString,
                ]) . '">Reset your password</a>'
            );
        }

        $this->addFlash('notice', 'Check your emails to reset your password');

        return $this->redirectToRoute('app_index_index');
    }

    #[Route(path: '/reset-password/{userId}/{verificationString}', name: 'app_reset_password')]
    public function resetPassword(
        int $userId,
        string $verificationString,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        Security $security,
    ): Response {
        $user = $this->userRepository->findOneBy([
            'id' => $userId,
            'email_verification_string' => $verificationString,
        ]);

        if (empty($verificationString) || 32 !== strlen($verificationString) || !$user) {
            $this->addFlash('warning', 'Account verification failed.');

            return $this->redirectToRoute('app_index_index');
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('security/reset_password.html.twig');
        }

        if (!$this->isPasswordStrong($request->get('password'))) {
            return $this->render('security/reset_password.html.twig', [
                'validation_failed' => true,
            ]);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $request->get('password'));

        $user
            ->setPassword($hashedPassword)
            ->setEmailVerified(true)
            ->setEmailVerificationString(null)
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $security->login($user, 'form_login');

        $this->addFlash('notice', 'Your password has been reset');

        return $this->redirectToRoute('app_index_index');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
