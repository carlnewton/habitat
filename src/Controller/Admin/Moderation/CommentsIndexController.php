<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class CommentsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    #[Route(path: '/admin/moderation/comments', name: 'app_moderation_comments', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response {
        return $this->renderTemplate($request, 'admin/moderation/comments.html.twig');
    }

    public function getFilters(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        $userEntities = $userRepository->findUsersWithComments();

        $users = [];
        foreach ($userEntities as $userEntity) {
            $user['value'] = $userEntity->getId();
            $user['label'] = $userEntity->getUsername();

            $users[] = $user;
        }

        return [
            'user' => [
                'label' => 'User',
                'type' => 'select',
                'options' => $users,
                'validation' => 'non-zero-integer',
            ],
        ];
    }

    public function getHeadings(): array
    {
        return [
            'body' => [
                'label' => $this->translator->trans('fields.comment.title'),
            ],
            'posted' => [
                'label' => $this->translator->trans('post.posted'),
                'sortable' => true,
            ],
            'user' => [
                'label' => $this->translator->trans('fields.user.title'),
            ],
            'post' => [
                'label' => $this->translator->trans('fields.post.title'),
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return $this->translator->trans('admin.moderation.comments.plural');
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
