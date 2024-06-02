<?php

namespace App\Controller\HTMX;

use App\Entity\Comment;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ListCommentsController extends AbstractController
{
    private const MAX_RESULTS_PER_PAGE = 10;

    #[Route(path: '/hx/list-comments', name: 'app_hx_list_comments', methods: ['GET'])]
    public function index(
        Request $request,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (empty($request->query->get('post'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $request->query->get('post')
        ]);

        if (empty($post)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $commentRepository = $entityManager->getRepository(Comment::class);

        $offset = 0;
        if (!empty($request->query->get('offset'))) {
            $offset = (int) $request->query->get('offset');
            if ($offset < 0) {
                $offset = 0;
            }
        }

        $comments = $commentRepository->findByPostId($post->getId(), $offset);

        return $this->render('partials/hx/list_comments.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'offset' => $offset + self::MAX_RESULTS_PER_PAGE,
        ]);
    }
}
