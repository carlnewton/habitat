<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class PostsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    #[Route(path: '/admin/moderation/posts', name: 'app_moderation_posts', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response {
        return $this->renderTemplate($request, 'admin/moderation/posts.html.twig');
    }

    public function getFilters(): array
    {
        $categoryRepository = $this->entityManager->getRepository(Category::class);

        $categoryEntities = $categoryRepository->findCategoriesWithPosts();

        $categories = [];
        foreach ($categoryEntities as $categoryEntity) {
            $category['value'] = $categoryEntity->getId();
            $category['label'] = $categoryEntity->getName();

            $categories[] = $category;
        }

        $userRepository = $this->entityManager->getRepository(User::class);

        $userEntities = $userRepository->findUsersWithPosts();

        $users = [];
        foreach ($userEntities as $userEntity) {
            $user['value'] = $userEntity->getId();
            $user['label'] = $userEntity->getUsername();

            $users[] = $user;
        }

        return [
            'category' => [
                'label' => $this->translator->trans('fields.category.title'),
                'type' => 'select',
                'options' => $categories,
                'validation' => 'non-zero-integer',
            ],
            'user' => [
                'label' => $this->translator->trans('fields.user.title'),
                'type' => 'select',
                'options' => $users,
                'validation' => 'non-zero-integer',
            ],
        ];
    }

    public function getHeadings(): array
    {
        return [
            'title' => [
                'label' => $this->translator->trans('fields.title.title'),
                'sortable' => true,
            ],
            'posted' => [
                'label' => $this->translator->trans('post.posted'),
                'sortable' => true,
            ],
            'category' => [
                'label' => $this->translator->trans('fields.category.title'),
            ],
            'user' => [
                'label' => $this->translator->trans('fields.user.title'),
            ],
            'attachments' => [
                'label' => $this->translator->trans('fields.attachments.title'),
                'sortable' => true,
                'type' => 'count',
            ],
            'comments' => [
                'label' => $this->translator->trans('fields.comments.title'),
                'sortable' => true,
                'type' => 'count',
            ],
            'hearts' => [
                'label' => $this->translator->trans('fields.hearts.title'),
                'sortable' => true,
                'type' => 'count',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return $this->translator->trans('admin.moderation.posts.plural');
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
