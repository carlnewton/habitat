<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class CommentsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/comments', name: 'app_moderation_comments', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->entityManager = $entityManager;

        return $this->renderTemplate($request, 'admin/moderation/comments.html.twig');
    }

    public function getHeadings(): array
    {
        return [
            'body' => [
                'label' => 'Comment',
            ],
            'posted' => [
                'label' => 'Posted',
                'sortable' => true,
            ],
            'user' => [
                'label' => 'User',
            ],
            'post' => [
                'label' => 'Post',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return 'comments';
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
        return Comment::class;
    }
}
