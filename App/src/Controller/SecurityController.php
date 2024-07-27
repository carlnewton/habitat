<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/signup', name: 'app_signup')]
    public function signup(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        MailerInterface $mailer,
        UrlGeneratorInterface $router
    ): Response {
        $userRepository = $entityManager->getRepository(User::class);

        if (0 === $userRepository->count()) {
            return $this->redirectToRoute('app_index_index');
        }

        $settingsRepository = $entityManager->getRepository(Settings::class);
        $registrationSetting = $settingsRepository->getSettingByName('registration');
        if (empty($registrationSetting) || 'on' !== $registrationSetting->getValue()) {
            $this->addFlash('warning', 'Registrations are currently disabled');

            return $this->redirectToRoute('app_index_index');
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('security/signup.html.twig');
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
                'values' => [
                    'username' => $request->get('username'),
                    'email' => $request->get('email'),
                ],
            ]);
        }

        $emailVerificationString = bin2hex(random_bytes(16));

        $user = new User();
        $user
            ->setUsername($request->get('username'))
            ->setCreated(new \DateTimeImmutable())
            ->setEmailAddress($request->get('email'))
            ->setEmailVerificationString($emailVerificationString)
        ;

        $hashedPassword = $passwordHasher->hashPassword($user, $request->get('password'));

        $user->setPassword($hashedPassword);

        $entityErrors = $validator->validate($user);
        if (count($entityErrors) > 0) {
            $this->addFlash('warning', 'Something went wrong with your details, please try again.');

            return $this->render('signup.html.twig');
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $domainSetting = $settingsRepository->getSettingByName('domain');
        $email = (new Email())
            ->from('admin@' . $domainSetting->getValue())
            ->to($user->getEmailAddress())
            ->subject('Verify your email address for ' . $domainSetting->getValue())
            ->html(
                '<p>Hello ' . $user->getUsername() . ',</p>' . 
                '<p>Click the link below to verify the email address for your account.</p>' . 
                '<p>Ignore this email if you didn\'t create this account.</p>' . 
                '<p><a href="https://' . $domainSetting->getValue() . $router->generate('app_verify_user', [
                    'userId' => $user->getId(),
                    'verificationString' => $emailVerificationString,
                ]) . '">Verify your email address</a>'
            )
        ;

        $thing = $mailer->send($email);

        $this->addFlash('notice', 'Check your emails to verify your email address');
        return $this->redirectToRoute('app_index_index');
    }

    private function validateSignUp(Request $request): array
    {
        $errors = [];

        if (empty($request->get('username')) || mb_strlen($request->get('username')) < User::USERNAME_MIN_LENGTH) {
            $errors['username'][] = 'Your username must be a minimum of ' . User::USERNAME_MIN_LENGTH . ' characters';
        }

        if (mb_strlen($request->get('username')) > User::USERNAME_MAX_LENGTH) {
            $errors['username'][] = 'Your username must be a maximum of ' . User::USERNAME_MAX_LENGTH . ' characters';
        }

        if (!empty($request->get('username') && !ctype_alnum($request->get('username')))) {
            $errors['username'][] = 'Your username must only use alphabetic and numeric characters';
        }

        if (empty($request->get('email')) || !filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'This is not a valid email address';
        }

        if (
            empty($request->get('password'))
            || mb_strlen($request->get('password') < User::PASSWORD_MIN_LENGTH)
            || !preg_match('/[A-Z]/', $request->get('password'))
            || !preg_match('/[a-z]/', $request->get('password'))
            || !preg_match('/[0-9]/', $request->get('password'))
        ) {
            $errors['password'][] = 'You must use a stronger password';
        }

        return $errors;
    }

    #[Route(path: '/verify/{userId}/{verificationString}', name: 'app_verify_user')]
    public function verifyUser(
        int $userId,
        string $verificationString,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([
            'id' => $userId,
            'email_verification_string' => $verificationString,
        ]);

        if (empty($verificationString) || strlen($verificationString) !== 32 || !$user) {
            $this->addFlash('warning', 'Account verification failed.');
            return $this->redirectToRoute('app_index_index');
        }

        $user->setEmailVerified(true);
        $user->setEmailVerificationString(NULL);

        $entityManager->persist($user);
        $entityManager->flush();

        $security->login($user);
        
        $this->addFlash('notice', 'Your account has been verified');
        return $this->redirectToRoute('app_index_index');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }
}
