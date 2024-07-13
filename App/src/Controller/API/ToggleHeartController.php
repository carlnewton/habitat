<?php

namespace App\Controller\API;

use App\Entity\Heart;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ToggleHeartController extends AbstractController
{
    #[Route(path: '/api/heart/{postId}', name: 'app_toggle_heart', methods: ['POST'])]
    public function index(
        int $postId,
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (null === $user) {
            throw $this->createAccessDeniedException('User is not signed in');
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $postId,
        ]);

        if (null === $post) {
            throw $this->createNotFoundException('The post does not exist');
        }

        $heartRepository = $entityManager->getRepository(Heart::class);

        $existingHeart = $heartRepository->findOneBy([
            'user' => $user,
            'post' => $post,
        ]);

        $actionTaken = 'added';
        if ($existingHeart) {
            $entityManager->remove($existingHeart);
            $actionTaken = 'removed';
        } else {
            $heart = new Heart();
            $heart->setUser($user);
            $heart->setPost($post);
            $entityManager->persist($heart);
        }
        $entityManager->flush();

        return new JsonResponse(
            [
                'result' => $actionTaken,
                'count' => $post->getHeartCount(),
            ]
        );
    }
}
