<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\User;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class UsersIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    #[Route(path: '/admin/moderation/users', name: 'app_moderation_users', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response {
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
                'label' => $this->translator->trans('fields.username.title'),
                'sortable' => true,
            ],
            'type' => [
                'label' => $this->translator->trans('fields.type.title'),
                'sortable' => false,
            ],
            'email_verified' => [
                'label' => $this->translator->trans('fields.verified.title'),
                'sortable' => true,
            ],
            'created' => [
                'label' => $this->translator->trans('fields.created.title'),
                'sortable' => true,
            ],
            'posts' => [
                'label' => $this->translator->trans('fields.posts.title'),
                'sortable' => true,
                'type' => 'count',
            ],
            'comments' => [
                'label' => $this->translator->trans('fields.comments.title'),
                'sortable' => true,
                'type' => 'count',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return $this->translator->trans('admin.moderation.users.plural');
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
