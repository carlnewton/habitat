<?php

namespace App\Controller\Admin\Moderation\Abstract;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AbstractAdminModerationController extends AbstractController
{
    protected const DEFAULT_ITEMS_PER_PAGE = 10;

    protected const ITEMS_PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected const SORT_ORDERS = ['asc', 'desc'];

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

        if (!in_array($order, self::SORT_ORDERS)) {
            $order = $this->getDefaultSortOrder();
        }

        return $this->getRepository()->findBy(
            [],
            [ $sort => $order ],
            $itemsPerPage,
            $itemsPerPage * ($page - 1)
        );

        return $items;
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
