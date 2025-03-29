<?php

namespace App\Controller\API;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\UserHiddenCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ToggleCategoryController extends AbstractController
{
    #[Route(path: '/api/category/{categoryId}', name: 'app_toggle_category', methods: ['POST'])]
    public function index(
        int $categoryId,
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (null === $user) {
            throw $this->createAccessDeniedException('User is not signed in');
        }

        $categoryRepository = $entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy([
            'id' => $categoryId,
        ]);

        if (null === $category) {
            throw $this->createNotFoundException('The category does not exist');
        }

        $hiddenCategoryRepository = $entityManager->getRepository(UserHiddenCategory::class);

        $existingHiddenCategory = $hiddenCategoryRepository->findOneBy([
            'user' => $user,
            'category' => $category,
        ]);

        $showPosts = false;
        if ($existingHiddenCategory) {
            $entityManager->remove($existingHiddenCategory);
            $showPosts = true;
        } else {
            $hiddenCategory = new UserHiddenCategory();
            $hiddenCategory->setUser($user);
            $hiddenCategory->setCategory($category);
            $entityManager->persist($hiddenCategory);
        }
        $entityManager->flush();

        return new JsonResponse(
            [
                'showPosts' => $showPosts,
            ]
        );
    }
}
