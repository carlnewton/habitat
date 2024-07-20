<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersSuspensionController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/users/suspend', name: 'app_moderation_users_suspend', methods: ['POST'], priority: 2)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->redirectToRoute('app_moderation_users');
        }

        $userIds = array_unique(array_map('intval', explode(',', $request->get('items'))));

        $userRepository = $entityManager->getRepository(User::class);

        $users = $userRepository->findBy(
            [
                'id' => $userIds,
                'suspended' => false,
            ]
        );

        if (empty($users)) {
            $this->addFlash(
                'warning',
                'The users could not be found.'
            );

            return $this->redirectToRoute('app_moderation_users');
        }

        if (empty($request->get('delete'))) {
            return $this->render('admin/moderation/suspend_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        foreach ($users as $user) {
            $user->setSuspended(true);

            foreach ($user->getPosts() as $post) {
                $post->setRemoved(true);
                $entityManager->persist($post);
            }

            foreach ($user->getComments() as $comment) {
                $comment->setRemoved(true);
                $entityManager->persist($comment);
            }

            $entityManager->persist($user);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Users suspended');

        return $this->redirectToRoute('app_moderation_users');
    }
}
