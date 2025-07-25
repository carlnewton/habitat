<?php

namespace App\Controller\Admin\Categories;

use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class AddEditCategoryController extends AbstractController
{
    #[Route(path: '/admin/categories/add', name: 'app_admin_categories_add', methods: ['GET', 'POST'])]
    #[Route(path: '/admin/categories/{id}', name: 'app_admin_categories_edit', methods: ['GET', 'POST'])]
    public function add(
        ?int $id,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $categoryRepository = $entityManager->getRepository(Category::class);

        $action = 'add';
        $category = new Category();
        if (!empty($id)) {
            $action = 'update';
            $category = $categoryRepository->find($id);
        }

        $categoryLocationOptions = CategoryLocationOptionsEnum::cases();

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('admin/categories/add_edit.html.twig');
            }

            $locationOption = CategoryLocationOptionsEnum::from($request->get('location'));
            $category
                ->setName($request->get('name'))
                ->setDescription($request->get('description'))
                ->setWeight((int) $request->get('weight'))
                ->setAllowPosting((bool) $request->get('allow-posting'))
            ;

            if (!empty($locationOption)) {
                $category->setLocation($locationOption);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/categories/add_edit.html.twig', [
                    'action' => $action,
                    'location_options' => $categoryLocationOptions,
                    'errors' => $fieldErrors,
                    'category' => $category,
                ]);
            }

            $entityManager->persist($category);

            $entityManager->flush();

            if ('add' === $action) {
                $this->addFlash('notice', 'Category added');
            } else {
                $this->addFlash('notice', 'Category updated');
            }

            return $this->redirectToRoute('app_admin_categories');
        }

        return $this->render('admin/categories/add_edit.html.twig', [
            'action' => $action,
            'category' => $category,
            'location_options' => $categoryLocationOptions,
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->get('name'))) {
            $errors['name'] = 'This field is required';
        }

        if (is_null($request->get('location')) || empty(CategoryLocationOptionsEnum::from($request->get('location')))) {
            $errors['location'] = 'This field is required';
        }

        return $errors;
    }
}
