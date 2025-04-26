<?php

namespace App\Command;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Report;
use App\Entity\Settings;
use App\Entity\User;
use App\Utilities\Mailer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'habitat:send-daily-digest-email')]
class SendDailyDigestEmailCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private Mailer $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yesterday = (new DateTime())->modify('-1 day');
        $queryBuilder = $this->entityManager->createQueryBuilder();

        $newUsers = $queryBuilder->select('u')
            ->from(User::class, 'u')
            ->where('u.created >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.daily_digest.new_users', ['%count%' => count($newUsers)]));

        $newPosts = $queryBuilder->select('p')
            ->from(Post::class, 'p')
            ->where('p.posted >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.daily_digest.new_posts', ['%count%' => count($newPosts)]));

        $newComments = $queryBuilder->select('c')
            ->from(Comment::class, 'c')
            ->where('c.posted >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.daily_digest.new_comments', ['%count%' => count($newComments)]));

        $newReports = $queryBuilder->select('r')
            ->from(Report::class, 'r')
            ->where('r.reported_date >= :yesterday')
            ->setParameter('yesterday', $yesterday)
            ->getQuery()
            ->getResult()
        ;
        $output->writeln($this->translator->trans('emails.daily_digest.new_reports', ['%count%' => count($newReports)]));

        if (empty($newUsers) && empty($newPosts) && empty($newComments) && empty($newReports)) {
            return Command::SUCCESS;
        }

        $domain = getenv('HABITAT_DOMAIN');

        $userRepository = $this->entityManager->getRepository(User::class);
        $admins = $userRepository->findUsersByRole('ROLE_SUPER_ADMIN');
        $admin = $admins[0];

        $subject = $this->translator->trans('emails.daily_digest.subject');
        $body = nl2br($this->translator->trans('emails.daily_digest.body', [
            '%admin%' => $admin->getUsername(),
        ]));

        if (!empty($newReports)) {
            $reportsModerationRoute = $this->urlGenerator->generate('app_moderation_reports');
            $body .= '<p>' . $this->translator->trans('emails.daily_digest.new_reports', ['%count%' => count($newReports)]) . '</p>';
            $body .= '<p><a href="https://' . $domain . $reportsModerationRoute . '">' . $this->translator->trans('emails.daily_digest.new_reports_link') . '</a></p>';
        }
        
        if (!empty($newPosts)) {
            $body .= '<p>' . $this->translator->trans('emails.daily_digest.new_posts', ['%count%' => count($newPosts)]) . '</p>';
            $body .= '<ul>';
            foreach ($newPosts as $newPost) {
                $viewPostRoute = $this->urlGenerator->generate('app_view_post', [
                    'id' => $newPost->getId(),
                ]);

                $body .= '<li><a href="https://' . $domain . $viewPostRoute . '">' . $newPost->getTitle() . '</a> - ' . $newPost->getUser()->getUsername() . '</li>';
            }
            $body .= '</ul>';
        }

        if (!empty($newComments)) {
            $body .= '<p>' . $this->translator->trans('emails.daily_digest.new_comments', ['%count%' => count($newComments)]) . '</p>';
            $body .= '<ul>';
            foreach ($newComments as $newComment) {
                $viewPostRoute = $this->urlGenerator->generate('app_view_post', [
                    'id' => $newComment->getPost()->getId(),
                ]);

                $body .= '<li><a href="https://' . $domain . $viewPostRoute . '">' . $newComment->getBody() . '</a> - ' . $newComment->getUser()->getUsername() . '</li>';
            }
            $body .= '</ul>';
        }

        if (!empty($newUsers)) {
            $body .= '<p>' . $this->translator->trans('emails.daily_digest.new_users', ['%count%' => count($newUsers)]) . '</p>';
            $body .= '<ul>';
            foreach ($newUsers as $newUser) {
                $userModerationRoute = $this->urlGenerator->generate('app_moderation_user', [
                    'id' => $newUser->getId(),
                ]);

                $body .= '<li><a href="https://' . $domain . $userModerationRoute . '">' . $newUser->getUsername() . '</a></li>';
            }
            $body .= '</ul>';
        }

        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $this->mailer->send(
            $admin->getEmailAddress(),
            $settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue(),
            $subject,
            $body
        );

        $output->writeln($this->translator->trans('emails.email_sent', ['%email_address%' => $admin->getEmailAddress()]));

        return Command::SUCCESS;
    }
}
