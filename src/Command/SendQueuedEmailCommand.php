<?php

namespace App\Command;

use App\Entity\Email;
use App\Repository\EmailRepository;
use App\Utilities\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCronTask('* * * * *')]
#[AsCommand(name: 'habitat:send-queued-email')]
class SendQueuedEmailCommand extends Command
{
    private const MAX_EMAILS = 5;

    protected EmailRepository $emailRepository;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator,
        protected Mailer $mailer,
    ) {
        $this->emailRepository = $this->entityManager->getRepository(Email::class);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emails = $this->emailRepository->findBy([], [
            'created' => 'ASC',
        ],
            self::MAX_EMAILS,
        );

        foreach ($emails as $email) {
            $this->mailer->send(
                $email->getToEmailAddress(),
                $email->getFromEmailAddress(),
                $email->getSubject(),
                $email->getBody()
            );

            $this->entityManager->remove($email);
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
