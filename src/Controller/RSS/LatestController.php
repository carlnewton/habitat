<?php

namespace App\Controller\RSS;

use App\Entity\Category;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LatestController extends AbstractController
{
    private const MAX_POSTS = 10;

    #[Route(path: '/rss/latest.xml', name: 'app_rss_latest', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
    ): Response {
        $postRepository = $entityManager->getRepository(Post::class);
        $categoryRepository = $entityManager->getRepository(Category::class);

        $posts = $postRepository->findBy(
            [],
            [
                'posted' => 'DESC',
            ],
            self::MAX_POSTS
        );

        $response = new Response(
            $this->renderView('rss/latest.xml.twig', [
                'posts' => $posts,
                'show_category' => $categoryRepository->count() > 1,
            ])
        );

        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }
}
