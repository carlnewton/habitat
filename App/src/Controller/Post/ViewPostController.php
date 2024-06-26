<?php

namespace App\Controller\Post;

use DateTimeImmutable;
use App\Entity\User;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_setup_admin');
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $id
        ]);

        if ($user !== null) {
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
