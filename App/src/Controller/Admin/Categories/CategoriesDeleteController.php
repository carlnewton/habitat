<?php

namespace App\Controller\Admin\Categories;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CategoriesDeleteController extends AbstractController
{
    #[Route(path: '/admin/categories/delete', name: 'app_admin_categories_delete', methods: ['POST'], priority: 2)]
    public function delete(
        ?int $id,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->redirectToRoute('app_admin_categories');
        }

        $categoryIds = array_unique(array_map('intval', explode(',', $request->get('items'))));

        $categoryRepository = $entityManager->getRepository(Category::class);

        $categories = $categoryRepository->findBy(
            [
                'id' => $categoryIds,
            ]
        );

        if (empty($categories)) {
            $this->addFlash(
                'warning',
                'The categories could not be found.'
            );

            return $this->redirectToRoute('app_admin_categories');
        }

        foreach ($categories as $category) {
            if (count($category->getPosts()) > 0) {
                $this->addFlash(
                    'warning',
                    'All posts must be assigned to a different category before a category can be deleted.'
                );

                return $this->redirectToRoute('app_admin_categories');
            }
        }

        if (empty($request->get('delete'))) {
            return $this->render('admin/categories/delete.html.twig', [
                'category_ids' => implode(',', $categoryIds),
                'categories' => $categories,
            ]);
        }

        foreach ($categories as $category) {
            $entityManager->remove($category);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Categories deleted');

        return $this->redirectToRoute('app_admin_categories');
    }
}
