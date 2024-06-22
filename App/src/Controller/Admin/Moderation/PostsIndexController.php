<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class PostsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/posts', name: 'app_moderation_posts', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->entityManager = $entityManager;

        return $this->renderTemplate($request, 'admin/moderation/posts.html.twig');
    }

    public function getHeadings(): array
    {
        return [
            'title' => [
                'label' => 'Title',
                'sortable' => true,
            ],
            'posted' => [
                'label' => 'Posted',
                'sortable' => true,
            ],
            'category' => [
                'label' => 'Category',
            ],
            'user' => [
                'label' => 'User',
            ],
            'attachments' => [
                'label' => 'Attachments',
            ],
            'comments' => [
                'label' => 'Comments',
            ],
            'hearts' => [
                'label' => 'Hearts',
            ]
        ];
    }

    public function getItemsLabel(): string
    {
        return 'posts';
    }

    public function getDefaultSortProperty(): string
    {
        return 'posted';
    }

    public function getDefaultSortOrder(): string
    {
        return 'desc';
    }

    public function getItemEntityClassName(): string
    {
        return Post::class;
    }
}
