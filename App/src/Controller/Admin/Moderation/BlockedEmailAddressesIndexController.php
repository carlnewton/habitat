<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\BlockedEmailAddress;
use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class BlockedEmailAddressesIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/blocked-email-addresses', name: 'app_moderation_blocked_email_addresses', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->entityManager = $entityManager;

        return $this->renderTemplate($request, 'admin/moderation/blocked_email_addresses.html.twig');
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getHeadings(): array
    {
        return [
            'email_address' => [
                'label' => 'Email address',
                'sortable' => true,
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return 'blocked email addresses';
    }

    public function getDefaultSortProperty(): string
    {
        return 'email_address';
    }

    public function getDefaultSortOrder(): string
    {
        return 'asc';
    }

    public function getItemEntityClassName(): string
    {
        return BlockedEmailAddress::class;
    }
}
