<?php

namespace App\Command;

use App\Entity\ModerationLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'habitat:expire-moderation-logs')]
class ExpireModerationLogsCommand extends Command
{
    private const RETENTION_TIME = '30 days';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $datetime = new \DateTime();
        $datetime->modify('-' . self::RETENTION_TIME);

        $moderationLogRepository = $this->entityManager->getRepository(ModerationLog::class);
        $moderationLogs = $moderationLogRepository->findBeforeDateTime($datetime);

        if (empty($moderationLogs)) {
            $output->writeln('No moderation logs to expire');

            return Command::SUCCESS;
        }

        $deletedCount = 0;
        foreach ($moderationLogs as $moderationLog) {
            $this->entityManager->remove($moderationLog);
            ++$deletedCount;
        }
        $this->entityManager->flush();

        $output->writeln(sprintf('Deleted %d moderation logs', $deletedCount));

        return Command::SUCCESS;
    }
}
