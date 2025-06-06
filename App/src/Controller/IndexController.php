<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class IndexController extends AbstractController
{
    private const MAX_POSTS = 10;

    #[Route(path: '/', name: 'app_index_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        $postRepository = $entityManager->getRepository(Post::class);
        $posts = $postRepository->findByHiddenCategories(
            [],
            [
                'posted' => 'DESC',
            ],
            self::MAX_POSTS,
            0,
            (null !== $user) ? $user->getId() : null,
        );

        $categoryRepository = $entityManager->getRepository(Category::class);

        return $this->render('index.html.twig', [
            'posts' => $posts,
            'offset' => self::MAX_POSTS,
            'show_category' => $categoryRepository->count() > 1,
        ]);
    }
}
