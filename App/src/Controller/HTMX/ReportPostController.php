<?php

namespace App\Controller\HTMX;

use App\Entity\Post;
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

class ReportPostController extends AbstractController
{
    #[Route(path: '/hx/report-post/{postId}', name: 'app_hx_report_post', methods: ['POST', 'GET'])]
    public function index(
        int $postId,
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $postId,
        ]);

        if (empty($post)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if ('GET' === $request->getMethod()) {
            return $this->render('partials/hx/report_post.html.twig', [
                'post_id' => $postId,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('report', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $report = new Report();
        $report
            ->setReportedDate(new \DateTimeImmutable())
            ->setPost($post)
            ->setReportedBy($user)
            ->setType('post')
            ->setReason($request->get('reason'))
        ;

        $entityManager->persist($report);
        $entityManager->flush();

        return $this->render('partials/hx/alert.html.twig', [
            'type' => 'success',
            'message' => $translator->trans('report_post.confirmation'),
        ]);
    }
}
