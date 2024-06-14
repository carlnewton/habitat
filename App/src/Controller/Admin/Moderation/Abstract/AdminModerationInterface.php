<?php

namespace App\Controller\Admin\Moderation\Abstract;

interface AdminModerationInterface
{
    public function getItemEntityClassName(): string;
    public function getDefaultSortProperty(): string;
    public function getDefaultSortOrder(): string;
    public function getHeadings(): array;
    public function getItemsLabel(): string;
}
