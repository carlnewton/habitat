<?php

namespace App\Controller\Admin\Categories;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $entityManager,
    ): Response {
        $this->entityManager = $entityManager;

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
                'label' => 'Category',
                'sortable' => true,
            ],
            'description' => [
                'label' => 'Description',
            ],
            'location' => [
                'label' => 'Location',
                'sortable' => true,
            ],
            'weight' => [
                'label' => 'Weight',
                'sortable' => true,
            ],
            'allow_posting' => [
                'label' => 'Allow posting',
                'sortable' => true,
            ],
            'posts' => [
                'label' => 'Posts',
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
