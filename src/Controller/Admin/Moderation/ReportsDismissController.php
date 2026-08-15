<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class ReportsDismissController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/reports/dismiss', name: 'app_moderation_reports_dismiss', methods: ['POST'], priority: 2)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->redirectToRoute('app_moderation_reports');
        }

        $reportIds = array_unique(array_map('intval', explode(',', $request->request->get('items'))));

        $reportRepository = $entityManager->getRepository(Report::class);

        $reports = $reportRepository->findBy([
            'id' => $reportIds,
        ]);

        if (empty($reports)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('admin.moderation.reports.not_found'),
            );

            return $this->redirectToRoute('app_moderation_reports');
        }

        if (empty($request->request->get('delete'))) {
            return $this->render('admin/moderation/dismiss_reports.html.twig', [
                'report_ids' => implode(',', $reportIds),
                'reports' => $reports,
            ]);
        }

        $reportsDismissed = false;
        foreach ($reports as $report) {
            $entityManager->remove($report);
            $reportsDismissed = true;
        }

        if ($reportsDismissed) {
            $entityManager->flush();
            $this->addFlash('notice', $this->translator->trans('admin.moderation.reports.dismissed'));
        }

        return $this->redirectToRoute('app_moderation_reports');
    }
}
