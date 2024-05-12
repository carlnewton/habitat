<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SetupController extends AbstractController
{
    #[Route(path: '/setup', name: 'app_setup_admin')]
    public function admin(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        Security $security
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() > 0) {
            return $this->redirectToRoute('app_index_index');
        }

        if ($request->getMethod() === 'POST') {
            $submittedToken = $request->getPayload()->get('token');

            if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('setup.html.twig');
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('setup.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'username' => $request->get('username'),
                        'email' => $request->get('email'),
                    ]
                ]);
            }

            $admin = new User();
            $admin
                ->setUsername($request->get('username'))
                ->setEmailAddress($request->get('email'))
                ->setRoles(['ROLE_SUPER_ADMIN'])
            ;

            $hashedPassword = $passwordHasher->hashPassword(
                $admin,
                $request->get('password')
            );

            $admin->setPassword($hashedPassword);

            $entityErrors = $validator->validate($admin);
            if (count($entityErrors) > 0) {
                $this->addFlash(
                    'warning',
                    'Something went wrong with your details, please try again.'
                );
                return $this->render('setup.html.twig');
            }

            $entityManager->persist($admin);
            $entityManager->flush();

            $security->login($admin);
            return $this->redirectToRoute('app_admin_index');
        }

        return $this->render('setup.html.twig');
    }

    protected function validateRequest(Request $request): array
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
}
