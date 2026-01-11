<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class OverviewController extends AbstractController
{
    private const RECENT_ITEMS_COUNT = 5;

    #[Route(path: '/admin', name: 'app_admin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $userRepository = $entityManager->getRepository(User::class);
        $userCount = $userRepository->count();
        $recentUsers = $userRepository->findBy([], ['created' => 'desc'], self::RECENT_ITEMS_COUNT);

        $postRepository = $entityManager->getRepository(Post::class);
        $postCount = $postRepository->count();
        $recentPosts = $postRepository->findBy([], ['posted' => 'desc'], self::RECENT_ITEMS_COUNT);

        $commentRepository = $entityManager->getRepository(Comment::class);
        $commentCount = $commentRepository->count();
        $recentComments = $commentRepository->findBy([], ['posted' => 'desc'], self::RECENT_ITEMS_COUNT);

        $reportRepository = $entityManager->getRepository(Report::class);
        $reportCount = $reportRepository->count();
        $recentReports = $reportRepository->findBy([], ['reported_date' => 'desc'], self::RECENT_ITEMS_COUNT);

        return $this->render('admin/index.html.twig', [
            'user_count' => $userCount,
            'post_count' => $postCount,
            'comment_count' => $commentCount,
            'report_count' => $reportCount,
            'recent_users' => $recentUsers,
            'recent_posts' => $recentPosts,
            'recent_comments' => $recentComments,
            'recent_reports' => $recentReports,
        ]);
    }
}
