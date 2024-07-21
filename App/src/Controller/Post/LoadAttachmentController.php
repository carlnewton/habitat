<?php

namespace App\Controller\Post;

use App\Entity\PostAttachment;
use App\Entity\User;
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

    private const UPLOADS_DIRECTORY = '/var/www/uploads/';

    #[Route(path: '/post/{postId}/attachment/{attachmentId}', name: 'app_load_attachment', methods: ['GET'])]
    public function load(
        int $postId,
        int $attachmentId,
        EntityManagerInterface $entityManager
    ): Response {
        $attachmentRepository = $entityManager->getRepository(PostAttachment::class);
        $attachment = $attachmentRepository->findOneBy([
            'id' => $attachmentId,
            'post' => $postId,
        ]);

        if (empty($attachment) || $attachment->getPost()->isRemoved()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filename = basename($attachment->getFilename());

        if (!file_exists(self::UPLOADS_DIRECTORY.$filename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse(self::UPLOADS_DIRECTORY.$filename);
    }

    #[Route(path: '/post/{postId}/attachment/thumbnail/{attachmentId}/{width}', name: 'app_load_attachment_thumbnail', methods: ['GET'])]
    public function load_create_thumbnail(
        int $postId,
        int $attachmentId,
        int $width,
        EntityManagerInterface $entityManager
    ): Response {
        if (!in_array($width, self::THUMBNAIL_WIDTHS)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $attachmentRepository = $entityManager->getRepository(PostAttachment::class);
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
        EntityManagerInterface $entityManager
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $attachmentRepository = $entityManager->getRepository(PostAttachment::class);
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

        if (!file_exists(self::UPLOADS_DIRECTORY.$originalFilename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filename = $width.'.'.$originalFilename;

        if (!file_exists(self::UPLOADS_DIRECTORY.$filename)) {
            list($originalWidth, $originalHeight) = getimagesize(self::UPLOADS_DIRECTORY.$originalFilename);
            if ($width >= $originalWidth) {
                return new BinaryFileResponse(self::UPLOADS_DIRECTORY.$originalFilename);
            }
            $height = ($originalHeight * $width) / $originalWidth;
            $thumbnail = imagecreatetruecolor($width, $height);

            switch (pathinfo(self::UPLOADS_DIRECTORY.$originalFilename, PATHINFO_EXTENSION)) {
                case 'jpg':
                case 'jpeg':
                    $source = imagecreatefromjpeg(self::UPLOADS_DIRECTORY.$originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagejpeg($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                    break;
                case 'gif':
                    $source = imagecreatefromgif(self::UPLOADS_DIRECTORY.$originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagegif($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                    break;
                case 'png':
                    $source = imagecreatefrompng(self::UPLOADS_DIRECTORY.$originalFilename);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagepng($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                    break;
                default:
                    return new Response('', Response::HTTP_NOT_FOUND);
            }
        }

        return new BinaryFileResponse(self::UPLOADS_DIRECTORY.$filename);
    }
}
