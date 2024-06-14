<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Moderation\Abstract\AbstractAdminModerationController;
use App\Controller\Admin\Moderation\Abstract\AdminModerationInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersController extends AbstractAdminModerationController implements AdminModerationInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/users', name: 'app_moderation_users', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $page = $request->get('page');
        if ((int) $page < 1) {
            $page = 1;
        }

        $sort = $request->get('sort');
        if (!in_array($sort, array_keys($this->getHeadings()))) {
            $sort = $this->getDefaultSortProperty();
        } else {
            $heading = $this->getHeadings()[$sort];
            if (!array_key_exists('sortable', $heading) || $heading['sortable'] !== true) {
                $sort = $this->getDefaultSortProperty();
            }
        }

        $order = $request->get('order');
        if (!in_array($order, self::SORT_ORDERS)) {
            $order = 'desc';
        }

        $itemsPerPage = $request->get('perPage');
        if (!in_array($itemsPerPage, self::ITEMS_PER_PAGE_OPTIONS)) {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        }

        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_setup_admin');
        }

        $this->entityManager = $entityManager;

        return $this->render('admin/moderation/users.html.twig', [
            'headings' => $this->getHeadings(),
            'items' => $this->getItems($page, $itemsPerPage, $sort, $order),
            'total_items' => $this->countTotalItems(),
            'total_pages' => ceil($this->countTotalItems() / $itemsPerPage),
            'current_page' => $page,
            'label' => $this->getItemsLabel(),
            'items_per_page' => $itemsPerPage,
            'items_per_page_options' => self::ITEMS_PER_PAGE_OPTIONS,
            'sort' => $sort,
            'order' => $order,
        ]);
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
            'created' => [
                'label' => 'Created',
                'sortable' => true,
            ],
            'posts' => [
                'label' => 'Posts',
            ],
            'comments' => [
                'label' => 'Comments',
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
