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
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class AddEditCategoryController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

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
                $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

                return $this->render('admin/categories/add_edit.html.twig');
            }

            $locationOption = CategoryLocationOptionsEnum::from($request->request->get('location'));
            $category
                ->setName($request->request->get('name'))
                ->setDescription($request->request->get('description'))
                ->setWeight((int) $request->request->get('weight'))
                ->setAllowPosting((bool) $request->request->get('allow-posting'))
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
                $this->addFlash('notice', $this->translator->trans('flash_messages.category_added'));
            } else {
                $this->addFlash('notice', $this->translator->trans('flash_messages.category_updated'));
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

        if (empty($request->request->get('name'))) {
            $errors['name'][] = $this->translator->trans('fields.category_name.validations.required');
        }

        if (is_null($request->request->get('location')) || empty(CategoryLocationOptionsEnum::from($request->request->get('location')))) {
            $errors['location'][] = $this->translator->trans('fields.category_location.validations.required');
        }

        return $errors;
    }
}
