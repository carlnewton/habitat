<?php

namespace App\Controller\Admin\Categories;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CategoriesIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    #[Route(path: '/admin/categories', name: 'app_admin_categories', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response {
        return $this->renderTemplate($request, 'admin/categories/index.html.twig');
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getHeadings(): array
    {
        return [
            'name' => [
                'label' => $this->translator->trans('fields.category.title'),
                'sortable' => true,
            ],
            'description' => [
                'label' => $this->translator->trans('fields.description.title'),
            ],
            'location' => [
                'label' => $this->translator->trans('fields.location.title'),
                'sortable' => true,
            ],
            'weight' => [
                'label' => $this->translator->trans('fields.weight.title'),
                'sortable' => true,
            ],
            'allow_posting' => [
                'label' => $this->translator->trans('post.allow_posting'),
                'sortable' => true,
            ],
            'posts' => [
                'label' => $this->translator->trans('fields.posts.title'),
                'sortable' => true,
                'type' => 'count',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return 'categories';
    }

    public function getDefaultSortProperty(): string
    {
        return 'name';
    }

    public function getDefaultSortOrder(): string
    {
        return 'asc';
    }

    public function getItemEntityClassName(): string
    {
        return Category::class;
    }
}
