<?php

namespace App\Controller\HTMX;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangeUsernameController extends AbstractController
{
    #[Route(path: '/hx/change-username', name: 'app_hx_change_username', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        UserRepository $userRepository,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('change_username', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($user->getUsername() === $request->get('username')) {
            return $this->render('partials/hx/change_username.html.twig', [
                'username' => $user->getUsername(),
            ]);
        }

        $errors = $this->validate($request, $translator);

        if (!empty($errors)) {
            return $this->render('partials/hx/change_username.html.twig', [
                'username' => $request->get('username'),
                'errors' => $errors,
            ]);
        }

        $existingUser = $userRepository->findOneBy([
            'username' => $request->get('username'),
        ]);
        if ($existingUser) {
            return $this->render('partials/hx/change_username.html.twig', [
                'username' => $request->get('username'),
                'errors' => [
                    $translator->trans('fields.username.validations.already_taken'),
                ],
            ]);
        }

        $user->setUsername($request->get('username'));
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('partials/hx/change_username.html.twig', [
            'username' => $user->getUsername(),
            'success' => true,
        ]);
    }

    private function validate(Request $request, TranslatorInterface $translator): array
    {
        $errors = [];

        if (empty($request->get('username')) || mb_strlen($request->get('username')) < User::USERNAME_MIN_LENGTH) {
            $errors[] = $translator->trans(
                'fields.username.validations.minimum_characters',
                [
                    '%character_length%' => User::USERNAME_MIN_LENGTH,
                ]
            );
        }

        if (mb_strlen($request->get('username')) > User::USERNAME_MAX_LENGTH) {
            $errors[] = $translator->trans(
                'fields.username.validations.maximum_characters',
                [
                    '%character_length%' => User::USERNAME_MAX_LENGTH,
                ]
            );
        }

        if (!empty($request->get('username') && !ctype_alnum($request->get('username')))) {
            $errors[] = $translator->trans('fields.username.validations.alphabetic_numeric');
        }

        return $errors;
    }
}
