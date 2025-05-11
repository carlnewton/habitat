<?php

namespace App\Controller\HTMX;

use App\Entity\ModerationLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ListModerationLogsController extends AbstractController
{
    private const MAX_RESULTS_PER_PAGE = 20;

    #[Route(path: '/hx/list-moderation-logs', name: 'app_hx_list_moderation_logs', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $offset = 0;
        if (!empty($request->query->get('offset'))) {
            $offset = (int) $request->query->get('offset');
            if ($offset < 0) {
                $offset = 0;
            }
        }

        $moderationLogRepository = $entityManager->getRepository(ModerationLog::class);
        $logs = $moderationLogRepository->findBy(
            [],
            ['date' => 'DESC'],
            self::MAX_RESULTS_PER_PAGE,
            $offset,
        );

        return $this->render('partials/hx/list_moderation_logs.html.twig', [
            'logs' => $logs,
            'offset' => $offset + self::MAX_RESULTS_PER_PAGE,
        ]);
    }
}
