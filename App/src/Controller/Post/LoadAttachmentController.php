<?php

namespace App\Controller\Post;

use App\Entity\PostAttachment;
use App\Entity\Settings;
use App\Entity\User;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class LoadAttachmentController extends AbstractController
{
    private const THUMBNAIL_WIDTHS = [
        550,
        700,
        830,
        970,
    ];

    private const LOCAL_UPLOADS_DIRECTORY = '/var/www/uploads/';

    private string $uploadPrefix;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/post/{postId}/attachment/{attachmentId}', name: 'app_load_attachment', methods: ['GET'])]
    public function load(
        int $postId,
        int $attachmentId,
    ): Response {
        $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);
        $attachment = $attachmentRepository->findOneBy([
            'id' => $attachmentId,
            'post' => $postId,
        ]);

        if (empty($attachment) || $attachment->getPost()->isRemoved()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filename = basename($attachment->getFilename());

        $this->setUploadPrefix();

        if (!file_exists($this->uploadPrefix . $filename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($this->uploadPrefix . $filename);
    }

    #[Route(path: '/post/{postId}/attachment/thumbnail/{attachmentId}/{width}', name: 'app_load_attachment_thumbnail', methods: ['GET'])]
    public function load_create_thumbnail(
        int $postId,
        int $attachmentId,
        int $width,
    ): Response {
        if (!in_array($width, self::THUMBNAIL_WIDTHS)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);
        $attachment = $attachmentRepository->findOneBy([
            'id' => $attachmentId,
            'post' => $postId,
        ]);

        if (empty($attachment) || $attachment->getPost()->isRemoved()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return $this->generateThumbnail($attachment, $width);
    }

    #[Route(path: '/attachment/unposted/{attachmentId}', name: 'app_load_unposted_attachment', methods: ['GET'])]
    public function loadUnposted(
        int $attachmentId,
        #[CurrentUser] ?User $user,
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);
        $attachment = $attachmentRepository->findOneBy([
            'id' => $attachmentId,
            'user' => $user->getId(),
        ]);

        if (empty($attachment)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return $this->generateThumbnail($attachment, 550);
    }

    private function generateThumbnail(PostAttachment $attachment, int $width): Response
    {
        $originalFilename = basename($attachment->getFilename());

        $this->setUploadPrefix();
        if (!file_exists($this->uploadPrefix . $originalFilename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filename = $width . '.' . $originalFilename;

        if (!file_exists($this->uploadPrefix . $filename)) {
            list($originalWidth, $originalHeight) = getimagesize($this->uploadPrefix . $originalFilename);
            if ($width >= $originalWidth) {
                return new BinaryFileResponse($this->uploadPrefix . $originalFilename);
            }
            $height = ($originalHeight * $width) / $originalWidth;
            $thumbnail = imagecreatetruecolor($width, $height);

            switch (pathinfo($this->uploadPrefix . $originalFilename, PATHINFO_EXTENSION)) {
                case 'jpg':
                case 'jpeg':
                    $source = imagecreatefromjpeg($this->uploadPrefix . $originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagejpeg($thumbnail, $this->uploadPrefix . $filename);
                    break;
                case 'gif':
                    $source = imagecreatefromgif($this->uploadPrefix . $originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagegif($thumbnail, $this->uploadPrefix . $filename);
                    break;
                case 'png':
                    $source = imagecreatefrompng($this->uploadPrefix . $originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagepng($thumbnail, $this->uploadPrefix . $filename);
                    break;
                default:
                    return new Response('', Response::HTTP_NOT_FOUND);
            }
        }

        return new BinaryFileResponse($this->uploadPrefix . $filename);
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
