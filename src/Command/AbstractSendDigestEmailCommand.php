<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Settings;
use App\Entity\User;
use App\Repository\SettingsRepository;
use App\Utilities\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSendDigestEmailCommand extends Command
{
    protected SettingsRepository $settingsRepository;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator,
        protected UrlGeneratorInterface $urlGenerator,
        protected Mailer $mailer,
    ) {
        $this->settingsRepository = $this->entityManager->getRepository(Settings::class);

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $smtpFromEmailAddress = $this->settingsRepository->getSettingByName('smtpFromEmailAddress');

        if (!$smtpFromEmailAddress) {
            $output->writeln($this->translator->trans('commands.digest_email.no_mail_configuration'));

            return Command::FAILURE;
        }

        $timeAgo = (new \DateTime())->modify('-' . $this->getDateRange());
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $newUsers = $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->where('u.created >= :timeAgo')
            ->setParameter('timeAgo', $timeAgo)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.digest.new_users', ['%count%' => count($newUsers)]));

        $newPosts = $queryBuilder->select('p')
            ->from(Post::class, 'p')
            ->where('p.posted >= :timeAgo')
            ->setParameter('timeAgo', $timeAgo)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.digest.new_posts', ['%count%' => count($newPosts)]));

        $newComments = $queryBuilder->select('c')
            ->from(Comment::class, 'c')
            ->where('c.posted >= :timeAgo')
            ->setParameter('timeAgo', $timeAgo)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.digest.new_comments', ['%count%' => count($newComments)]));

        $newReports = $queryBuilder->select('r')
            ->from(Report::class, 'r')
            ->where('r.reported_date >= :timeAgo')
            ->setParameter('timeAgo', $timeAgo)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.digest.new_reports', ['%count%' => count($newReports)]));

        if (empty($newUsers) && empty($newPosts) && empty($newComments) && empty($newReports)) {
            return Command::SUCCESS;
        }

        $domain = getenv('SERVER_NAME');

        $userRepository = $this->entityManager->getRepository(User::class);
        $admins = $userRepository->findUsersByRole('ROLE_SUPER_ADMIN');
        $admin = $admins[0];

        $subject = $this->translator->trans('emails.digest.subject');
        $body = nl2br($this->translator->trans('emails.digest.body', [
            '%admin%' => $admin->getUsername(),
        ]));

        if (!empty($newReports)) {
            $reportsModerationRoute = $this->urlGenerator->generate('app_moderation_reports');
            $body .= '<p>' .
                $this->translator->trans('emails.digest.new_reports', ['%count%' => count($newReports)]) .
                '</p><p><a href="' . $domain . $reportsModerationRoute . '">' .
                $this->translator->trans('emails.digest.new_reports_link') . '</a></p>'
            ;
        }

        if (!empty($newPosts)) {
            $body .= '<p>' .
                $this->translator->trans('emails.digest.new_posts', ['%count%' => count($newPosts)]) .
                '</p><ul>'
            ;
            foreach ($newPosts as $newPost) {
                $viewPostRoute = $this->urlGenerator->generate('app_view_post', [
                    'id' => $newPost->getId(),
                ]);

                $body .= '<li><a href="' . $domain . $viewPostRoute . '">' . $newPost->getTitle() . '</a> - ' .
                    $newPost->getUser()->getUsername() . '</li>'
                ;
            }
            $body .= '</ul>';
        }

        if (!empty($newComments)) {
            $body .= '<p>' .
                $this->translator->trans('emails.digest.new_comments', ['%count%' => count($newComments)]) .
                '</p><ul>'
            ;
            foreach ($newComments as $newComment) {
                $viewPostRoute = $this->urlGenerator->generate('app_view_post', [
                    'id' => $newComment->getPost()->getId(),
                ]);

                $body .= '<li><a href="' . $domain . $viewPostRoute . '">' . trim(strip_tags($newComment->getBody())) .
                    '</a> - ' . $newComment->getUser()->getUsername() . '</li>'
                ;
            }
            $body .= '</ul>';
        }

        if (!empty($newUsers)) {
            $body .= '<p>' .
                $this->translator->trans('emails.digest.new_users', ['%count%' => count($newUsers)]) .
                '</p><ul>'
            ;
            foreach ($newUsers as $newUser) {
                $userModerationRoute = $this->urlGenerator->generate('app_moderation_user', [
                    'id' => $newUser->getId(),
                ]);

                $body .= '<li><a href="' . $domain . $userModerationRoute . '">' .
                    $newUser->getUsername() .
                    '</a></li>'
                ;
            }
            $body .= '</ul>';
        }

        $body .= '<p>' .
            $this->translator->trans('emails.digest.change_frequency') .
            ' <a href="' . $domain . $this->urlGenerator->generate('app_settings') . '">' .
            $this->translator->trans('emails.digest.change_frequency_settings_page') . '</a>.</p>'
        ;

        $this->mailer->send(
            $admin->getEmailAddress(),
            $smtpFromEmailAddress->getValue(),
            $subject,
            $body
        );

        $output->writeln(
            $this->translator->trans('emails.email_sent', ['%email_address%' => $admin->getEmailAddress()])
        );

        return Command::SUCCESS;
    }
}
