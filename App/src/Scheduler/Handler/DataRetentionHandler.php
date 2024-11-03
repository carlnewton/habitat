<?php

namespace App\Scheduler\Handler;

use App\Entity\BlockedEmailAddress;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Settings;
use App\Entity\User;
use App\Scheduler\Message\DataRetention;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DataRetentionHandler
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
    }

    public function __invoke(DataRetention $message): void
    {
        $this->deleteComments();
        $this->deletePosts();
        $this->deleteUserData();
    }

    private function deleteUserData(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        $users = $userRepository->findSuspendedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($users)) {
            return;
        }

        foreach ($users as $user) {
            $hearts = $user->getHearts();
            foreach ($hearts as $heart) {
                $this->entityManager->remove($heart);
            }

            $comments = $user->getComments();
            foreach ($comments as $comment) {
                $this->entityManager->remove($comment);
            }

            $posts = $user->getPosts();
            foreach ($posts as $post) {
                $attachments = $post->getAttachments();
                foreach ($attachments as $attachment) {
                    if (!empty(basename($attachment->getFilename()))) {
                        $files = $this->finder->files()->in($this->getAttachmentDirectory())->name('*' . basename($attachment->getFilename()));
                        foreach ($files as $file) {
                            $this->filesystem->remove($file->getRealPath());
                        }
                    }
                    $this->entityManager->remove($attachment);
                }

                $this->entityManager->remove($post);
            }

            $this->blockEmailAddress($user->getEmailAddress());
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
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

    private function deletePosts(): void
    {
        $postRepository = $this->entityManager->getRepository(Post::class);

        $posts = $postRepository->findRemovedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($posts)) {
            return;
        }

        foreach ($posts as $post) {
            $attachments = $post->getAttachments();
            foreach ($attachments as $attachment) {
                if (!empty(basename($attachment->getFilename()))) {
                    $files = $this->finder->files()->in($this->getAttachmentDirectory())->name('*' . basename($attachment->getFilename()));
                    foreach ($files as $file) {
                        $this->filesystem->remove($file->getRealPath());
                    }
                }
                $this->entityManager->remove($attachment);
            }

            $this->entityManager->remove($post);
        }

        $this->entityManager->flush();
    }

    private function deleteComments(): void
    {
        $commentRepository = $this->entityManager->getRepository(Comment::class);

        $comments = $commentRepository->findRemovedBeforeRelativeTime(self::RETENTION_TIME);

        if (empty($comments)) {
            return;
        }

        foreach ($comments as $comment) {
            $this->entityManager->remove($comment);
        }

        $this->entityManager->flush();
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
