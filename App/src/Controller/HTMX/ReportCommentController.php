<?php

namespace App\Controller\HTMX;

use App\Entity\Comment;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportCommentController extends AbstractController
{
    #[Route(path: '/hx/report-comment/{commentId}', name: 'app_hx_report_comment', methods: ['POST', 'GET'])]
    public function index(
        int $commentId,
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $commentRepository = $entityManager->getRepository(Comment::class);
        $comment = $commentRepository->findOneBy([
            'id' => $commentId,
        ]);

        if (empty($comment)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ('GET' === $request->getMethod()) {
            return $this->render('partials/hx/report_comment.html.twig', [
                'comment_id' => $commentId,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('report', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $report = new Report();
        $report
            ->setReportedDate(new \DateTimeImmutable())
            ->setComment($comment)
            ->setReportedBy($user)
            ->setType('comment')
            ->setReason($request->get('reason'))
        ;

        $entityManager->persist($report);
        $entityManager->flush();

        return $this->render('partials/hx/alert.html.twig', [
            'type' => 'success',
            'message' => $translator->trans('report_comment.confirmation'),
        ]);
    }
}
