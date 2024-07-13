<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CommentsRemovalController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/comments/remove', name: 'app_moderation_comments_remove', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->redirectToRoute('app_moderation_comments');
        }

        $commentIds = array_unique(array_map('intval', explode(',', $request->get('items'))));

        $commentRepository = $entityManager->getRepository(Comment::class);

        $comments = $commentRepository->findBy(
            [
                'id' => $commentIds,
                'removed' => false,
            ]
        );

        if (empty($comments)) {
            $this->addFlash(
                'warning',
                'The comments could not be found.'
            );

            return $this->redirectToRoute('app_moderation_comments');
        }

        if (empty($request->get('delete'))) {
            return $this->render('admin/moderation/remove_comments.html.twig', [
                'comment_ids' => implode(',', $commentIds),
                'comments' => $comments,
            ]);
        }

        foreach ($comments as $comment) {
            $comment->setRemoved(true);
            $entityManager->persist($comment);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Comments removed');

        return $this->redirectToRoute('app_moderation_comments');
    }
}
