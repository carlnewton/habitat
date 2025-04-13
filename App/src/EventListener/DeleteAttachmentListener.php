<?php

namespace App\EventListener;

use App\Entity\PostAttachment;
use App\Entity\Settings;
use Aws\S3\S3Client;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: PostAttachment::class)]
class DeleteAttachmentListener
{
    private const THUMBNAIL_WIDTHS = [
        550,
        700,
        830,
        970,
    ];

    private const LOCAL_UPLOADS_DIRECTORY = '/var/www/uploads/';

    private string $uploadPrefix;

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function preRemove(PostAttachment $attachment, PreRemoveEventArgs $event): void
    {
        $filename = basename($attachment->getFilename());

        if (empty($filename)) {
            return;
        }

        $this->setUploadPrefix();

        $filesToDelete = [
            $this->uploadPrefix . $filename,
        ];
        
        foreach (self::THUMBNAIL_WIDTHS as $width) {
            $filesToDelete[] = $this->uploadPrefix . $width . '.' . $filename;
        }

        foreach ($filesToDelete as $filepath) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    private function setUploadPrefix(): void
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $imageStorageSetting = $settingsRepository->getSettingByName('imageStorage');

        if (empty($imageStorageSetting) || 's3' !== $imageStorageSetting->getValue()) {
            $this->uploadPrefix = self::LOCAL_UPLOADS_DIRECTORY;

            return;
        }

        $s3Client = new S3Client([
            'region' => $settingsRepository->getSettingByName('s3Region')->getValue(),
            'credentials' => [
                'key' => $settingsRepository->getSettingByName('s3AccessKey')->getValue(),
                'secret' => $settingsRepository->getSettingByName('s3SecretKey')->getEncryptedValue(),
            ],
        ]);

        $s3Client->registerStreamWrapper();

        $bucketSetting = $settingsRepository->getSettingByName('s3BucketName');
        $this->uploadPrefix = 's3://' . $bucketSetting->getValue() . '/';
    }
}
