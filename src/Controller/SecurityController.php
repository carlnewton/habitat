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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    private UserRepository $userRepository;
    private RegistrationQuestionRepository $registrationQuestionRepository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
        private TranslatorInterface $translator,
    ) {
        $this->userRepository = $entityManager->getRepository(User::class);
        $this->registrationQuestionRepository = $entityManager->getRepository(RegistrationQuestion::class);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        #[CurrentUser] ?User $user,
    ): Response {
        if ('1' === $request->query->get('admin')) {
            $admins = $this->userRepository->findUsersByRole('ROLE_SUPER_ADMIN');
            if (empty($admins)) {
                return $this->redirectToRoute('app_setup_language');
            }

            if ($user) {
                return $this->redirectToRoute('app_setup_language');
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastEmailAddress = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_email_address' => $lastEmailAddress,
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
            $this->addFlash('warning', $this->translator->trans('account.registration.messages.registration_disabled'));

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
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->render('security/signup.html.twig');
        }

        $fieldErrors = $this->validateSignUp($request);

        if (!empty($fieldErrors)) {
            return $this->render('security/signup.html.twig', [
                'errors' => $fieldErrors,
                'question' => $newRegistrationQuestion,
                'values' => [
                    'username' => trim($request->request->get('username')),
                    'email' => trim($request->request->get('email')),
                ],
            ]);
        }

        $emailVerificationString = bin2hex(random_bytes(16));

        // We handle existing email address validation here, and not in the validator to give the same end user
        // experience so as to prevent email address harvesting.

        $existingEmailAddress = $this->userRepository->findOneBy([
            'email_address' => trim($request->request->get('email')),
        ]);

        $blockedEmailAddressRepository = $this->entityManager->getRepository(BlockedEmailAddress::class);
        $blockedEmailAddress = $blockedEmailAddressRepository->findOneBy([
            'email_address' => trim($request->request->get('email')),
        ]);

        // Do not attempt to invert this to reduce indentation, we want the same flash message (and any other behaviour)
        // we add at the end.
        if (empty($existingEmailAddress) && empty($blockedEmailAddress)) {
            $user = new User();
            $user
                ->setUsername(trim($request->request->get('username')))
                ->setCreated(new \DateTimeImmutable())
                ->setEmailAddress(trim($request->request->get('email')))
                ->setEmailVerificationString($emailVerificationString)
            ;

            $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));

            $user->setPassword($hashedPassword);

            $entityErrors = $validator->validate($user);
            if (count($entityErrors) > 0) {
                $this->addFlash('warning', $this->translator->trans('account.validations.generic'));

                return $this->render('signup.html.twig', [
                    'question' => $newRegistrationQuestion,
                ]);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $domain = getenv('SERVER_NAME');

            $mailer->send(
                $user->getEmailAddress(),
                $settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue(),
                $this->translator->trans('emails.verify_email_address.subject', [
                    '%domain%' => $domain
                ]),
                nl2br($this->translator->trans('emails.verify_email_address.body', [
                    '%username%' => $user->getUsername()
                ])) . '<p><a href="' . $domain . $router->generate('app_verify_user', [
                        'userId' => $user->getId(),
                        'verificationString' => $emailVerificationString,
                    ]) . '">' . $this->translator->trans('buttons.verify_email_address') . '</a>'
            );
        }

        $this->addFlash('notice', $this->translator->trans('flash_messages.check_emails'));

        return $this->redirectToRoute('app_index_index');
    }

    private function validateSignUp(Request $request): array
    {
        $errors = [];

        if (empty(trim($request->request->get('username'))) || mb_strlen(trim($request->request->get('username'))) < User::USERNAME_MIN_LENGTH) {
            $errors['username'][] = $this->translator->trans(
                'fields.username.validations.minimum_characters',
                [
                    '%character_length%' => User::USERNAME_MIN_LENGTH,
                ]
            );
        }

        if (mb_strlen(trim($request->request->get('username'))) > User::USERNAME_MAX_LENGTH) {
            $errors['username'][] = $this->translator->trans(
                'fields.username.validations.maximum_characters',
                [
                    '%character_length%' => User::USERNAME_MAX_LENGTH,
                ]
            );
        }

        if (!empty(trim($request->request->get('username')) && !ctype_alnum(trim($request->request->get('username'))))) {
            $errors['username'][] = $this->translator->trans('fields.username.validations.alphabetic_numeric');
        }

        $existingUser = $this->userRepository->findOneBy([
            'username' => trim($request->request->get('username')),
        ]);

        if ($existingUser) {
            $errors['username'][] = $this->translator->trans('fields.username.validations.already_taken');
        }

        if (empty(trim($request->request->get('email'))) || !filter_var(trim($request->request->get('email')), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = $this->translator->trans('fields.email_address.validations.invalid_email_address');
        }

        if (!User::isPasswordStrong($request->request->get('password'))) {
            $errors['password'][] = $this->translator->trans('fields.password.validations.weak_password');
        }

        if ($this->registrationQuestionRepository->count() > 0) {
            $question = $this->registrationQuestionRepository->findOneBy([
                'id' => (int) $request->request->get('question'),
            ]);

            if (empty($question)) {
                $errors['question'][] = $this->translator->trans('account.sign_up.challenge.validations.try_again');
            } elseif ('' === $request->request->get('answer')) {
                $errors['question'][] = $this->translator->trans('account.sign_up.challenge.validations.empty');
            } elseif (!$this->questionHasAnswer($question, $request->request->get('answer'))) {
                $errors['question'][] = $this->translator->trans('account.sign_up.challenge.validations.incorrect');
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
            $this->addFlash('warning', $this->translator->trans('flash_messages.check_emails'));

            return $this->redirectToRoute('app_index_index');
        }

        $user->setEmailVerified(true);
        $user->setEmailVerificationString(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $security->login($user, 'security.authenticator.form_login.main');

        $this->addFlash('notice', $this->translator->trans('flash_messages.account_verified'));

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

        $emailAddress = trim($request->request->get('email'));

        $user = $this->userRepository->findOneBy([
            'email_address' => $emailAddress,
        ]);

        if ($user) {
            $emailVerificationString = bin2hex(random_bytes(16));
            $user->setEmailVerificationString($emailVerificationString);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $settingsRepository = $this->entityManager->getRepository(Settings::class);
            $domain = getenv('SERVER_NAME');

            $mailer->send(
                $user->getEmailAddress(),
                $settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue(),
                $this->translator->trans('emails.password_reset.subject', [
                    '%domain%' => $domain
                ]),
                nl2br($this->translator->trans('emails.password_reset.body', [
                    '%username%' => $user->getUsername()
                ])) . '<p><a href="' . $domain . $router->generate('app_reset_password', [
                        'userId' => $user->getId(),
                        'verificationString' => $emailVerificationString,
                    ]) . '">' . $this->translator->trans('buttons.reset_password') . '</a>'
            );
        }

        $this->addFlash('notice', $this->translator->trans('flash_messages.check_emails_reset_password'));

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
            $this->addFlash('warning', $this->translator->trans('flash_messages.account_verification_failed'));

            return $this->redirectToRoute('app_index_index');
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('security/reset_password.html.twig');
        }

        if (!User::isPasswordStrong($request->request->get('password'))) {
            return $this->render('security/reset_password.html.twig', [
                'validation_failed' => true,
            ]);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('password'));

        $user
            ->setPassword($hashedPassword)
            ->setEmailVerified(true)
            ->setEmailVerificationString(null)
        ;

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $security->login($user, 'form_login');

        $this->addFlash('notice', $this->translator->trans('flash_messages.password_reset'));

        return $this->redirectToRoute('app_index_index');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
