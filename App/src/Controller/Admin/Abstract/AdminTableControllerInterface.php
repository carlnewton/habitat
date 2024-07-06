<?php

namespace App\Controller\Admin\Abstract;

interface AdminTableControllerInterface
{
    public function getItemEntityClassName(): string;
    public function getDefaultSortProperty(): string;
    public function getDefaultSortOrder(): string;
    public function getHeadings(): array;
    public function getFilters(): array;
    public function getItemsLabel(): string;
}
