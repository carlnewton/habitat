<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\Comment;
use App\Entity\ModerationLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CommentsDeleteController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/comments/delete', name: 'app_moderation_comments_delete', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
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
            return $this->render('admin/moderation/delete_comments.html.twig', [
                'comment_ids' => implode(',', $commentIds),
                'comments' => $comments,
            ]);
        }

        $fieldErrors = $this->validate($request);

        if (!empty($fieldErrors)) {
            return $this->render('admin/moderation/delete_comments.html.twig', [
                'comment_ids' => implode(',', $commentIds),
                'comments' => $comments,
                'errors' => $fieldErrors,
                'values' => [
                    'reason' => $request->get('reason'),
                ],
            ]);
        }

        foreach ($comments as $comment) {
            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($this->getUser())
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.delete_comment', [
                    '%comment%' => $comment->getBody(),
                    '%username%' => $comment->getUser()->getUsername(),
                    '%post_title%' => $comment->getPost()->getTitle(),
                    '%reason%' => $request->get('reason'),
                ]));

            $entityManager->persist($moderationLog);

            $entityManager->remove($comment);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Comments deleted');

        return $this->redirectToRoute('app_moderation_comments');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (strlen($request->get('reason')) > 255) {
            $errors['reason'][] = 'The value of this field must be a maximum of 255 characters';
        } elseif (empty(trim($request->get('reason')))) {
            $errors['reason'][] = 'This is a required field';
        }

        return $errors;
    }
}
