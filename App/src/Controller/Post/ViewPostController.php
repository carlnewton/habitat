<?php

namespace App\Controller\Post;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ViewPostController extends AbstractController
{
    #[Route(path: '/post/{id}', name: 'app_view_post', methods: ['GET'])]
    public function index(
        int $id,
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $id,
        ]);

        if (!$post || $post->isRemoved()) {
            throw $this->createNotFoundException('The post does not exist');
        }

        if (null !== $user) {
            foreach ($post->getHearts() as $heart) {
                if ($heart->getUser()->getId() === $user->getId()) {
                    $post->setCurrentUserHearted(true);
                    break;
                }
            }
        }

        return $this->render('view_post.html.twig', [
            'post' => $post,
        ]);
    }
}
