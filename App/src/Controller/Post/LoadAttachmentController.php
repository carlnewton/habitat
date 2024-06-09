<?php

namespace App\Controller\Post;

use App\Entity\PostAttachment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoadAttachmentController extends AbstractController
{
    #[Route(path: '/post/{postId}/attachment/{attachmentId}', name: 'app_load_attachment', methods: ['GET'])]
    public function load(
        int $postId,
        int $attachmentId,
        EntityManagerInterface $entityManager
): Response
    {
        $attachmentRepository = $entityManager->getRepository(PostAttachment::class);
        $attachment = $attachmentRepository->findOneBy([
            'id' => $attachmentId,
            'post' => $postId,
        ]);

        if (empty($attachment)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filename = basename($attachment->getFilename());

        if (!file_exists('/var/www/uploads/' . $filename)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse('/var/www/uploads/' . $filename);
    }
}
