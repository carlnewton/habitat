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

        if (!file_exists('/var/www/uploads/'.$filename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse('/var/www/uploads/'.$filename);
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

        $filename = basename($attachment->getFilename());

        if (!file_exists('/var/www/uploads/'.$filename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse('/var/www/uploads/'.$filename);
    }
}
