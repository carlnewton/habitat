<?php

namespace App\Controller\Admin\Moderation;

use App\Controller\Admin\Abstract\AbstractAdminTableController;
use App\Controller\Admin\Abstract\AdminTableControllerInterface;
use App\Entity\Report;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class ReportsIndexController extends AbstractAdminTableController implements AdminTableControllerInterface
{
    #[Route(path: '/admin/moderation/reports', name: 'app_moderation_reports', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
    ): Response {
        return $this->renderTemplate($request, 'admin/moderation/reports.html.twig');
    }

    public function getFilters(): array
    {
        return [
            'type' => [
                'label' => $this->translator->trans('fields.type.title'),
                'type' => 'select',
                'options' => [
                    [
                        'label' => $this->translator->trans('fields.comment.title'),
                        'value' => 'comment',
                    ],
                    [
                        'label' => $this->translator->trans('fields.post.title'),
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
                'label' => $this->translator->trans('fields.type.title'),
            ],
            'reported_date' => [
                'label' => $this->translator->trans('fields.date_reported.title'),
                'sortable' => true,
            ],
            'reported_by' => [
                'label' => $this->translator->trans('fields.reported_by.title'),
            ],
            'reason' => [
                'label' => $this->translator->trans('admin.actions.reason'),
            ],
            'context' => [
                'label' => $this->translator->trans('fields.context.title'),
            ],
        ];
    }

    public function getItemsLabel(): string
    {
        return $this->translator->trans('admin.moderation.reports.plural');
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
