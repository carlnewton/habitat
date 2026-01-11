<?php

namespace App\Controller\HTMX;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\UserHiddenCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ToggleCategoryController extends AbstractController
{
    #[Route(path: '/hx/toggle_category/{categoryId}', name: 'app_hx_toggle_category', methods: ['POST'])]
    public function index(
        int $categoryId,
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('toggle_category', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $categoryRepository = $entityManager->getRepository(Category::class);
        $category = $categoryRepository->findOneBy([
            'id' => $categoryId,
        ]);

        if (null === $category) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $categoryHidden = $user->hasHiddenCategory($category->getId());
        $hiddenCategoryRepository = $entityManager->getRepository(UserHiddenCategory::class);

        if ($categoryHidden) {
            $existingHiddenCategory = $hiddenCategoryRepository->findOneBy([
                'user' => $user,
                'category' => $category,
            ]);

            $entityManager->remove($existingHiddenCategory);
        } else {
            $hiddenCategory = new UserHiddenCategory();
            $hiddenCategory->setUser($user);
            $hiddenCategory->setCategory($category);
            $entityManager->persist($hiddenCategory);
        }

        $entityManager->flush();

        return $this->render('partials/hx/toggle_category.html.twig', [
            'category' => $category,
            'categoryHidden' => !$categoryHidden,
        ]);
    }
}
