<?php

namespace App\Controller\Admin\Categories;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use App\Entity\User;
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
        EntityManagerInterface $entityManager
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_setup_admin');
        }

        $categoryRepository = $entityManager->getRepository(Category::class);

        $action = 'add';
        $category = new Category();
        if (!empty($id)) {
            $action = 'update';
            $category = $categoryRepository->find($id);
        }

        $categoryLocationOptions = CategoryLocationOptionsEnum::cases();

        if ($request->getMethod() === 'POST') {
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
                    'category' => $category
                ]);
            }

            $entityManager->persist($category);

            $entityManager->flush();

            if ($action === 'add') {
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

        if (is_null($request->get('location') || empty(CategoryLocationOptionsEnum::from($request->get('location'))))) {
            $errors['location'] = 'This field is required';
        }

        return $errors;
    }
}
