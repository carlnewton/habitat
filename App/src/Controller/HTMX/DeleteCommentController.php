<?php

namespace App\Controller\HTMX;

use App\Entity\Comment;
use App\Entity\ModerationLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteCommentController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/hx/delete-comment', name: 'app_hx_delete_comment', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        if (empty($request->get('commentId'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $commentRepository = $entityManager->getRepository(Comment::class);
        $comment = $commentRepository->findOneBy([
            'id' => $request->get('commentId'),
        ]);

        if (empty($comment)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('comment', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($comment->getUser()->getId() !== $user->getId() && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($user)
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.delete_comment', [
                    '%comment%' => $comment->getBody(),
                    '%username%' => $comment->getUser()->getUsername(),
                    '%post_title%' => $comment->getPost()->getTitle(),
                ]));

            $entityManager->persist($moderationLog);
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        return new Response('', Response::HTTP_OK);
    }
}
