<?php

namespace App\Controller\HTMX;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordController extends AbstractController
{
    #[Route(path: '/hx/change-password', name: 'app_hx_change_password', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('change_password', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $errors = $this->validate($request, $user, $passwordHasher, $translator);

        if (!empty($errors)) {
            return $this->render('partials/hx/change_password.html.twig', [
                'errors' => $errors,
            ]);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $request->request->get('new_password'));
        $user->setPassword($hashedPassword);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('partials/hx/change_password.html.twig', [
            'success' => true,
        ]);
    }

    private function validate(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator): array
    {
        $errors = [];

        if (!$passwordHasher->isPasswordValid($user, (string) $request->request->get('current_password'))) {
            $errors[] = $translator->trans('fields.password.validations.incorrect_current_password');
        }

        if (!$this->isPasswordStrong($request->request->get('new_password'))) {
            $errors[] = $translator->trans('fields.password.validations.weak_password');
        }

        if ($request->request->get('new_password') !== $request->request->get('confirm_password')) {
            $errors[] = $translator->trans('fields.password.validations.passwords_do_not_match');
        }

        return $errors;
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
}
