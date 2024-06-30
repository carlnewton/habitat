<?php

namespace App\Controller\Admin\Abstract;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AbstractAdminTableController extends AbstractController
{
    protected const DEFAULT_ITEMS_PER_PAGE = 10;

    protected const ITEMS_PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected const SORT_ORDERS = ['asc', 'desc'];

    protected function renderTemplate(Request $request, string $templatePath)
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_setup_admin');
        }

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
            $order = $this->getDefaultSortOrder();
        }

        $itemsPerPage = $request->get('perPage');
        if (!in_array($itemsPerPage, self::ITEMS_PER_PAGE_OPTIONS)) {
            $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE;
        }

        return $this->render($templatePath, [
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
    protected function getItems(int $page, int $itemsPerPage, ?string $sort, ?string $order): array
    {
        if (!in_array($sort, array_keys($this->getHeadings()))) {
            $sort = $this->getDefaultSortProperty();
        } else {
            $heading = $this->getHeadings()[$sort];
            if (!array_key_exists('sortable', $heading) || $heading['sortable'] !== true) {
                $sort = $this->getDefaultSortProperty();
            }
        }

        $orderByCount = false;
        if (array_key_exists('type', $this->getHeadings()[$sort]) && $this->getHeadings()[$sort]['type'] === 'count') {
            $orderByCount = true;
            if ($order === 'asc') {
                $order = 'desc';
            } else {
                $order = 'asc';
            }
        }

        if (!in_array($order, self::SORT_ORDERS)) {
            $order = $this->getDefaultSortOrder();
        }

        if ($orderByCount) {
            return $this->getRepository()->findByAssocCount(
                [],
                [ $sort => $order ],
                $itemsPerPage,
                $itemsPerPage * ($page - 1),
            );
        }

        return $this->getRepository()->findBy(
            [],
            [ $sort => $order ],
            $itemsPerPage,
            $itemsPerPage * ($page - 1),
        );
    }

    protected function countTotalItems(): int
    {
        return $this->getRepository()->count();
    }

    protected function getRepository(): ServiceEntityRepository
    {
        return $this->entityManager->getRepository($this->getItemEntityClassName());
    }
}
