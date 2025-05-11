<?php

namespace App\Controller;

use App\Entity\ModerationLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ModerationLogController extends AbstractController
{
    private const MAX_LOGS = 20;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/moderation-log', name: 'app_moderation_log', methods: ['GET'])]
    public function index(): Response
    {
        $moderationLogRepository = $this->entityManager->getRepository(ModerationLog::class);
        $logs = $moderationLogRepository->findBy(
            [],
            [
                'date' => 'DESC',
            ],
            self::MAX_LOGS,
        );

        return $this->render('moderation_log.html.twig', [
            'logs' => $logs,
            'offset' => self::MAX_LOGS,
        ]);
    }
}
