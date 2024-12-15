<?php

namespace App\Command;

use App\Entity\BlockedEmailAddress;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Settings;
use App\Entity\User;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'habitat:data-retention')]
class DataRetentionCommand extends Command
{
    /**
     * RETENTION_TIME will use a DateTime relative format unit eg '1 minute', '2 hours', '3 days', '4 years' etc.
     */
    private const RETENTION_TIME = '30 days';

    private const LOCAL_ATTACHMENT_DIRECTORY = '/var/www/uploads/';

    private string $attachmentDirectory;
    private Finder $finder;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem,
    ) {
        $this->finder = new Finder();

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Comments');
        $this->deleteComments($output);
        $output->writeln('Posts');
        $this->deletePosts($output);
        $output->writeln('Users');
        $this->deleteUserData($output);

        return Command::SUCCESS;
    }

    private function deleteUserData(OutputInterface $output): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        $output->writeln('- Finding users suspended more than ' . self::RETENTION_TIME . ' ago ...');
        $users = $userRepository->findSuspendedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($users)) {
            $output->writeln('- No users found.');

            return;
        }

        $userCount = 0;
        foreach ($users as $user) {
            $output->writeln('- Deleting user ' . ++$userCount . '/' . count($users));
            $hearts = $user->getHearts();
            $output->writeln('  - Deleting ' . count($hearts) . ' user hearts.');
            foreach ($hearts as $heart) {
                $this->entityManager->remove($heart);
            }

            $comments = $user->getComments();
            $output->writeln('  - Deleting ' . count($comments) . ' user comments.');
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }

            $posts = $user->getPosts();
            $postCount = 0;
            foreach ($posts as $post) {
                $output->writeln('  - Deleting post ' . ++$postCount . '/' . count($posts));
                $attachments = $post->getAttachments();
                $attachmentCount = 0;
                foreach ($attachments as $attachment) {
                    $output->writeln('    - Deleting post attachment ' . ++$attachmentCount . '/' . count($attachments));
                    if (!empty(basename($attachment->getFilename()))) {
                        $files = $this->finder->files()->in($this->getAttachmentDirectory())->name('*' . basename($attachment->getFilename()));
                        foreach ($files as $file) {
                            $output->writeln('      - Deleting post attachment file ' . $file->getFilename());
                            $this->filesystem->remove($file->getRealPath());
                        }
                    }
                    $this->entityManager->remove($attachment);
                }

                $this->entityManager->remove($post);
            }

            $this->blockEmailAddress($user->getEmailAddress());
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
        $output->writeln('- ' . count($users) . ' users successfully deleted.');
    }

    private function blockEmailAddress(string $emailAddress): void
    {
        $blockedEmailAddressRepository = $this->entityManager->getRepository(BlockedEmailAddress::class);
        $blockedEmailAddress = $blockedEmailAddressRepository->findOneBy([
            'email_address' => $emailAddress,
        ]);

        if (empty($blockedEmailAddress)) {
            return;
        }

        $blockedEmailAddress = new BlockedEmailAddress();
        $blockedEmailAddress->setEmailAddress($emailAddress);
        $this->entityManager->persist($blockedEmailAddress);
        $this->entityManager->flush();
    }

    private function deletePosts(OutputInterface $output): void
    {
        $postRepository = $this->entityManager->getRepository(Post::class);

        $output->writeln('- Finding posts removed more than ' . self::RETENTION_TIME . ' ago ...');
        $posts = $postRepository->findRemovedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($posts)) {
            $output->writeln('- No posts found.');

            return;
        }

        $postCount = 0;
        foreach ($posts as $post) {
            $output->writeln('- Deleting post ' . ++$postCount . '/' . count($posts));
            $attachments = $post->getAttachments();
            $attachmentCount = 0;
            foreach ($attachments as $attachment) {
                $output->writeln('  - Deleting post attachment ' . ++$attachmentCount . '/' . count($attachments));
                if (!empty(basename($attachment->getFilename()))) {
                    $files = $this->finder->files()->in($this->getAttachmentDirectory())->name('*' . basename($attachment->getFilename()));
                    foreach ($files as $file) {
                        $output->writeln('    - Deleting post attachment file ' . $file->getFilename());
                        $this->filesystem->remove($file->getRealPath());
                    }
                }
                $this->entityManager->remove($attachment);
            }

            $this->entityManager->remove($post);
        }

        $this->entityManager->flush();
        $output->writeln('- ' . count($posts) . ' posts successfully deleted.');
    }

    private function deleteComments(OutputInterface $output): void
    {
        $commentRepository = $this->entityManager->getRepository(Comment::class);

        $output->writeln('- Finding comments removed more than ' . self::RETENTION_TIME . ' ago ...');
        $comments = $commentRepository->findRemovedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($comments)) {
            $output->writeln('- No comments found.');

            return;
        }

        $commentCount = 0;
        foreach ($comments as $comment) {
            $output->writeln('- Deleting comment ' . ++$commentCount . '/' . count($comments));
            $this->entityManager->remove($comment);
        }

        $this->entityManager->flush();
        $output->writeln('- ' . count($comments) . ' comments successfully deleted.');
    }

    private function getAttachmentDirectory(): string
    {
        if (!empty($this->attachmentDirectory)) {
            return $this->attachmentDirectory;
        }

        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $imageStorageSetting = $settingsRepository->getSettingByName('imageStorage');

        $uploadPrefix = self::LOCAL_ATTACHMENT_DIRECTORY;
        if (!empty($imageStorageSetting) && 's3' === $imageStorageSetting->getValue()) {
            $s3Client = new S3Client([
                'region' => $settingsRepository->getSettingByName('s3Region')->getValue(),
                'credentials' => [
                    'key' => $settingsRepository->getSettingByName('s3AccessKey')->getValue(),
                    'secret' => $settingsRepository->getSettingByName('s3SecretKey')->getEncryptedValue(),
                ],
            ]);

            $s3Client->registerStreamWrapper();

            $bucketSetting = $settingsRepository->getSettingByName('s3BucketName');
            $uploadPrefix = 's3://' . $bucketSetting->getValue() . '/';
        }

        $this->attachmentDirectory = $uploadPrefix;

        return $this->attachmentDirectory;
    }
}
