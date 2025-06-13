<?php

namespace App\Utilities;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class Mailer
{
    private SettingsRepository $settingsRepository;

    private SymfonyMailer $symfonyMailer;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {
        $this->settingsRepository = $entityManager->getRepository(Settings::class);
    }

    public function sendTest(
        string $smtpUsername,
        string $smtpPassword,
        string $smtpServer,
        int $smtpPort,
        string $to,
        string $from,
    ) {
        $transport = $this->getTransport($smtpUsername, $smtpPassword, $smtpServer, $smtpPort);
        $this->symfonyMailer = new SymfonyMailer($transport);

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject('Habitat test email')
            ->html('This is a test email sent from Habitat.')
        ;

        $this->symfonyMailer->send($email);
    }

    public function send(string $to, string $from, string $subject, string $body)
    {
        if ('dev' === getenv('APP_ENV')) {
            $transport = Transport::fromDsn(getenv('MAILER_DSN'));
        } else {
            $username = $this->settingsRepository->getSettingByName('smtpUsername');
            $password = $this->settingsRepository->getSettingByName('smtpPassword');
            $server = $this->settingsRepository->getSettingByName('smtpServer');
            $port = $this->settingsRepository->getSettingByName('smtpPort');

            $transport = Transport::fromDsn(sprintf('smtp://%s:%s@%s:%s',
                $username->getValue(),
                $password->getEncryptedValue(),
                $server->getValue(),
                $port->getValue()
            ));
        }

        $this->symfonyMailer = new SymfonyMailer($transport);

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($body)
        ;

        $this->symfonyMailer->send($email);
    }

    private function getTransport(string $username, string $password, string $server, int $port): TransportInterface
    {
        return Transport::fromDsn(sprintf('smtp://%s:%s@%s:%d',
            $username,
            $password,
            $server,
            $port,
        ));
    }
}
