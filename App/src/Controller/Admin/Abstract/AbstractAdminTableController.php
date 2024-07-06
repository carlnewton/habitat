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

        $itemFilters = $this->generateItemFilterArray($request);

        return $this->render($templatePath, [
            'filters' => $this->getFilters(),
            'filtered' => $itemFilters,
            'headings' => $this->getHeadings(),
            'items' => $this->getItems($itemFilters, $page, $itemsPerPage, $sort, $order),
            'total_items' => $this->countTotalItems($itemFilters),
            'total_items_unfiltered' => $this->countTotalItems(),
            'total_pages' => ceil($this->countTotalItems($itemFilters) / $itemsPerPage),
            'current_page' => $page,
            'label' => $this->getItemsLabel(),
            'items_per_page' => $itemsPerPage,
            'items_per_page_options' => self::ITEMS_PER_PAGE_OPTIONS,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    protected function generateItemFilterArray(Request $request): array
    {
        if (empty($this->getFilters())) {
            return [];
        }

        $filters = [];
        foreach ($this->getFilters() as $filterName => $filterProperties) {
            if (!$request->get($filterName)) {
                continue;
            }

            switch ($filterProperties['validation']) {
                case 'non-zero-integer':
                    if ((int) $request->get($filterName) > 0) {
                        $filters[$filterName] = (int) $request->get($filterName);
                    }
                    break;
                default:
                    break;
            }
        }

        return $filters;
    }

    protected function getItems(array $filters, int $page, int $itemsPerPage, ?string $sort, ?string $order): array
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
                $filters,
                [ $sort => $order ],
                $itemsPerPage,
                $itemsPerPage * ($page - 1),
            );
        }

        return $this->getRepository()->findBy(
            $filters,
            [ $sort => $order ],
            $itemsPerPage,
            $itemsPerPage * ($page - 1),
        );
    }

    protected function countTotalItems(array $filters = []): int
    {
        return $this->getRepository()->count($filters);
    }

    protected function getRepository(): ServiceEntityRepository
    {
        return $this->entityManager->getRepository($this->getItemEntityClassName());
    }
}
