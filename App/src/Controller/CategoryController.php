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

class CategoryController extends AbstractController
{
    private const MAX_POSTS = 10;

    #[Route(path: '/category/{id}', name: 'app_category_index', methods: ['GET', 'POST'])]
    public function index(
        int $id,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        $categoryRepository = $entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy([
            'id' => $id,
        ]);

        if (!$category) {
            throw $this->createNotFoundException('The category does not exist');
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $posts = $postRepository->findBy(
            [
                'category' => $category,
            ],
            [
                'posted' => 'DESC',
            ],
            self::MAX_POSTS
        );

        $categoryHidden = false;
        if (null !== $user) {
            $categoryHidden = $user->hasHiddenCategory($category->getId());
        }

        return $this->render('category.html.twig', [
            'posts' => $posts,
            'offset' => self::MAX_POSTS,
            'category' => $category,
            'categoryHidden' => $categoryHidden,
        ]);
    }
}
