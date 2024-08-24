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

        try {
            if ($originalWidth <= self::MAXIMUM_IMAGE_WIDTH) {
                $width = $originalWidth;
                $height = $originalHeight;
            } else {
                $width = self::MAXIMUM_IMAGE_WIDTH;
                $height = ($originalHeight * self::MAXIMUM_IMAGE_WIDTH) / $originalWidth;
            }

            $thumbnail = imagecreatetruecolor($width, $height);
            switch ($file->getMimeType()) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($file);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    $thumbnail = $this->rotateJpeg(exif_read_data($file), $thumbnail);
                    imagejpeg($thumbnail, self::UPLOADS_DIRECTORY . $filename);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($file);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagegif($thumbnail, self::UPLOADS_DIRECTORY . $filename);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($file);
                    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
                    imagepng($thumbnail, self::UPLOADS_DIRECTORY . $filename);
                    break;
            }
        } catch (FileException $e) {
            echo $e;
            exit;

            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        list($newWidth, $newHeight) = getimagesize(self::UPLOADS_DIRECTORY . $filename);

        $attachment = new PostAttachment();
        $attachment
            ->setFilename($filename)
            ->setWidth((int) $newWidth)
            ->setHeight((int) $newHeight)
            ->setUser($user)
        ;

        $entityManager->persist($attachment);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $attachment->getId(),
        ]);
    }

    private function rotateJpeg(?array $exifData, $image)
    {
        if (empty($exifData['Orientation'])) {
            return $image;
        }

        switch($exifData['Orientation']) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                break;
            case 5:
                $image = imagerotate($image, -90, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 7:
                $image = imagerotate($image, 90, 0);
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 8:
                $image = imagerotate($image, 90, 0); 
                break;
        }

        return $image;
    }
}
