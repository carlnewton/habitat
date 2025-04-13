<?php

namespace App\Command;

use App\Entity\PostAttachment;
use App\Entity\Settings;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'habitat:cleanup-attachments')]
class CleanupAttachmentsCommand extends Command
{
    private const LOCAL_ATTACHMENT_DIRECTORY = '/var/www/uploads/';
    private const RETENTION_TIME = '1 day';

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
        $allFiles = $this->finder->files()->in($this->getAttachmentDirectory());
        $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);
        foreach ($allFiles as $file) {
            if (strtotime('-' . self::RETENTION_TIME) < $file->getMTime()) {
                $output->writeln('Skipping ' . $file->getFilename() . ' as it was created too recently.');
                continue;
            }

            $checkFilename = $file->getFilename();

            // If it's a thumbnail, we want to check for the fullsize image reference in the database
            if (preg_match('/^[0-9]{3}\./', $checkFilename)) {
                $checkFilename = substr($checkFilename, 4);
            }

            $attachmentEntity = $attachmentRepository->findOneBy([
                'filename' => $checkFilename,
            ]);

            if (!empty($attachmentEntity)) {
                if (!is_null($attachmentEntity->getPost())) {
                    $output->writeln('Ignoring ' . $file->getFilename() . ' as it is in use.');
                    continue;
                }

                $output->writeln('Deleting entity for ' . $file->getFilename());
                $this->entityManager->remove($attachmentEntity);
                $this->entityManager->flush();
            }

            // The file is likely already deleted from the event action associated with the entity, but this will also
            // delete anything that didn't make it into the database for whatever reason.
            if (!empty($file->getRealPath())) {
                $output->writeln('Deleting ' . $file->getFilename());
                $this->filesystem->remove($file->getRealPath());
            }
        }

        return Command::SUCCESS;
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
