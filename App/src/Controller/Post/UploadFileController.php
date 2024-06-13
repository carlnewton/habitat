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
    private const ALLOWED_MIMETYPES = [
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    #[Route(path: '/post/upload', name: 'app_upload_file', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($user === null) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        if (!$request->files->has('file')) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $file = $request->files->get('file');

        if (!in_array($file->getMimeType(), self::ALLOWED_MIMETYPES)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $imageSize = getimagesize($file);

        if (!$imageSize) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        list($width, $height) = $imageSize;

        $filename = implode('.', [
            date('Y-m-d'),
            date('H-i-s'),
            $user->getId(),
            $user->getUsername(),
            uniqid(),
            $file->guessExtension(),
        ]);

        try {
            $file->move('/var/www/uploads', $filename);
        } catch (FileException $e) {
            echo $e; exit;
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
