<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class ReportsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/reports', name: 'app_moderation_reports', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->entityManager = $entityManager;

        return $this->renderTemplate($request, 'admin/moderation/reports.html.twig');
    }

    public function getFilters(): array
    {
        return [
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    [
                        'label' => 'Comment',
                        'value' => 'comment',
                    ],
                    [
                        'label' => 'Post',
                        'value' => 'post',
                    ],
                ],
                'validation' => 'alphabetic',
            ],
        ];
    }

    public function getHeadings(): array
    {
        return [
            'type' => [
                'label' => 'Type',
            ],
            'reported_date' => [
                'label' => 'Date reported',
                'sortable' => true,
            ],
            'reported_by' => [
                'label' => 'Reported by',
            ],
            'reason' => [
                'label' => 'Reason',
            ],
            'context' => [
                'label' => 'Context',
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return 'reports';
    }

    public function getDefaultSortProperty(): string
    {
        return 'reported_date';
    }

    public function getDefaultSortOrder(): string
    {
        return 'desc';
    }

    public function getItemEntityClassName(): string
    {
        return Report::class;
    }
}
