<?php

namespace App\Controller\HTMX;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ListPostsController extends AbstractController
{
    private const MAX_RESULTS_PER_PAGE = 10;

    #[Route(path: '/hx/list-posts', name: 'app_hx_list_posts', methods: ['GET'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager
    ): Response
    {
        $postRepository = $entityManager->getRepository(Post::class);

        $offset = 0;
        if (!empty($request->query->get('offset'))) {
            $offset = (int) $request->query->get('offset');
            if ($offset < 0) {
                $offset = 0;
            }
        }

        $posts = $postRepository->findBy([], ['posted' => 'DESC'], self::MAX_RESULTS_PER_PAGE, $offset);

        if ($user !== null) {
            foreach ($posts as $post) {
                foreach ($post->getHearts() as $heart) {
                    if ($heart->getUser()->getId() === $user->getId()) {
                        $post->setCurrentUserHearted(true);
                        break;
                    }
                }
            }
        }

        return $this->render('partials/hx/list_posts.html.twig', [
            'posts' => $posts,
            'offset' => $offset + self::MAX_RESULTS_PER_PAGE,
        ]);
    }
}
