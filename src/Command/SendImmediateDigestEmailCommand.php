<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('* * * * *')]
#[AsCommand(name: 'habitat:send-immediate-digest-email')]
class SendImmediateDigestEmailCommand extends AbstractSendDigestEmailCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $digest = $this->settingsRepository->getSettingByName('digestEmail');
        if ($digest && 'immediately' !== $digest->getValue()) {
            $output->writeln($this->translator->trans('commands.digest_email.skipped'));

            return Command::SUCCESS;
        }

        return parent::execute($input, $output);
    }

    protected function getDateRange(): string
    {
        return '1 minute';
    }
}
