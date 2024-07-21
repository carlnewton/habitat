<?php

namespace App\Controller\Post;

use App\Entity\PostAttachment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UploadFileController extends AbstractController
{
    private const MAXIMUM_IMAGE_WIDTH = 1920;

    private const UPLOADS_DIRECTORY = '/var/www/uploads/';

    private const ALLOWED_MIMETYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    #[Route(path: '/post/upload', name: 'app_upload_file', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        if (!$request->files->has('file')) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $file = $request->files->get('file');

        if (!in_array($file->getMimeType(), self::ALLOWED_MIMETYPES)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $originalImageSize = getimagesize($file);

        if (!$originalImageSize) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        list($originalWidth, $originalHeight) = $originalImageSize;

        $filename = implode('.', [
            date('Y-m-d'),
            date('H-i-s'),
            $user->getId(),
            $user->getUsername(),
            uniqid(),
            $file->guessExtension(),
        ]);

        $width = $originalWidth;
        $height = $originalHeight;
        try {
            if ($originalWidth <= self::MAXIMUM_IMAGE_WIDTH) {
                $file->move('/var/www/uploads', $filename);
            } else {
                $width = self::MAXIMUM_IMAGE_WIDTH;
                $height = ($originalHeight * $width) / $originalWidth;
                $thumbnail = imagecreatetruecolor($width, $height);
                switch ($file->getMimeType()) {
                    case 'image/jpeg':
                        $source = imagecreatefromjpeg($file);
                        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                        imagejpeg($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                        break;
                    case 'image/gif':
                        $source = imagecreatefromgif(self::UPLOADS_DIRECTORY.$originalFilename);
                        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                        imagegif($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                        break;
                    case 'image/png':
                        $source = imagecreatefrompng(self::UPLOADS_DIRECTORY.$originalFilename);
                        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                        imagepng($thumbnail, self::UPLOADS_DIRECTORY.$filename);
                        break;
                }
            }
        } catch (FileException $e) {
            echo $e;
            exit;

            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $attachment = new PostAttachment();
        $attachment
            ->setFilename($filename)
            ->setWidth((int) $width)
            ->setHeight((int) $height)
            ->setUser($user)
        ;

        $entityManager->persist($attachment);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $attachment->getId(),
        ]);
    }
}
