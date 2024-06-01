<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
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
        Security $security
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_index_index');
        }

        if ($request->getMethod() !== 'POST') {
            return $this->render('security/signup.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('signup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('security/signup.html.twig');
        }

        $fieldErrors = $this->validateSignUp($request);

        if (!empty($fieldErrors)) {
            return $this->render('security/signup.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'username' => $request->get('username'),
                    'email' => $request->get('email'),
                ]
            ]);
        }

        $user = new User();
        $user
            ->setUsername($request->get('username'))
            ->setEmailAddress($request->get('email'))
        ;

        $hashedPassword = $passwordHasher->hashPassword($user, $request->get('password'));

        $user->setPassword($hashedPassword);

        $entityErrors = $validator->validate($user);
        if (count($entityErrors) > 0) {
            $this->addFlash(
                'warning',
                'Something went wrong with your details, please try again.'
            );
            return $this->render('signup.html.twig');
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $security->login($user);

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
            empty($request->get('password')) || 
            mb_strlen($request->get('password') < User::PASSWORD_MIN_LENGTH) ||
            !preg_match('/[A-Z]/', $request->get('password')) ||
            !preg_match('/[a-z]/', $request->get('password')) ||
            !preg_match('/[0-9]/', $request->get('password')) 
        ) {
            $errors['password'][] = 'You must use a stronger password';
        }

        return $errors;
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
