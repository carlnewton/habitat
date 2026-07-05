<?php

namespace App\Controller\Admin\Categories;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CategoriesDeleteController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/categories/delete', name: 'app_admin_categories_delete', methods: ['POST'], priority: 2)]
    public function delete(
        ?int $id,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->redirectToRoute('app_admin_categories');
        }

        $categoryIds = array_unique(array_map('intval', explode(',', $request->request->get('items'))));

        $categoryRepository = $entityManager->getRepository(Category::class);

        $categories = $categoryRepository->findBy(
            [
                'id' => $categoryIds,
            ]
        );

        if (empty($categories)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('admin.categories.not_found')
            );

            return $this->redirectToRoute('app_admin_categories');
        }

        foreach ($categories as $category) {
            if (count($category->getPosts()) > 0) {
                $this->addFlash(
                    'warning',
                    $this->translator->trans('admin.categories.assign_to_delete')
                );

                return $this->redirectToRoute('app_admin_categories');
            }
        }

        if (empty($request->request->get('delete'))) {
            return $this->render('admin/categories/delete.html.twig', [
                'category_ids' => implode(',', $categoryIds),
                'categories' => $categories,
            ]);
        }

        foreach ($categories as $category) {
            $entityManager->remove($category);
        }
        $entityManager->flush();

        $this->addFlash('notice', $this->translator->trans('admin.categories.deleted'));

        return $this->redirectToRoute('app_admin_categories');
    }
}
