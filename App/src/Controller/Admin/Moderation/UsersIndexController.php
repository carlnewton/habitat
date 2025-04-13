<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/users', name: 'app_moderation_users', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->entityManager = $entityManager;

        return $this->renderTemplate($request, 'admin/moderation/users.html.twig');
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getHeadings(): array
    {
        return [
            'username' => [
                'label' => 'Username',
                'sortable' => true,
            ],
            'email_address' => [
                'label' => 'Email address',
                'sortable' => true,
            ],
            'email_verified' => [
                'label' => 'Verified',
                'sortable' => true,
            ],
            'created' => [
                'label' => 'Created',
                'sortable' => true,
            ],
            'posts' => [
                'label' => 'Posts',
                'sortable' => true,
                'type' => 'count',
            ],
            'comments' => [
                'label' => 'Comments',
                'sortable' => true,
                'type' => 'count',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return 'users';
    }

    public function getDefaultSortProperty(): string
    {
        return 'created';
    }

    public function getDefaultSortOrder(): string
    {
        return 'desc';
    }

    public function getItemEntityClassName(): string
    {
        return User::class;
    }
}
