<?php

namespace App\Controller\HTMX;

use App\Entity\Heart;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HeartPostController extends AbstractController
{
    #[Route(path: '/hx/heart-post/{postId}', name: 'app_hx_heart_post', methods: ['POST'])]
    public function index(
        int $postId,
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('heart', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($user->isFrozen() || !$user->isEmailVerified()) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $postId,
        ]);

        if (null === $post) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $heartRepository = $entityManager->getRepository(Heart::class);

        $existingHeart = $heartRepository->findOneBy([
            'user' => $user,
            'post' => $post,
        ]);

        if ($existingHeart) {
            $entityManager->remove($existingHeart);
        } else {
            $heart = new Heart();
            $heart->setUser($user);
            $heart->setPost($post);
            $entityManager->persist($heart);
        }
        $entityManager->flush();

        return $this->render('partials/hx/heart.html.twig', [
            'post' => $post,
        ]);
    }
}
