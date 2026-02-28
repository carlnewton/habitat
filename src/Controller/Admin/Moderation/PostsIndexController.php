<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class PostsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/posts', name: 'app_moderation_posts', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->entityManager = $entityManager;

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
                'label' => 'Category',
                'type' => 'select',
                'options' => $categories,
                'validation' => 'non-zero-integer',
            ],
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
                'sortable' => true,
                'type' => 'count',
            ],
            'comments' => [
                'label' => 'Comments',
                'sortable' => true,
                'type' => 'count',
            ],
            'hearts' => [
                'label' => 'Hearts',
                'sortable' => true,
                'type' => 'count',
            ],
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
