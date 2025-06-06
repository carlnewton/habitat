<?php

namespace App\Controller\HTMX;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ListNearbyPostsController extends AbstractController
{
    private const MAX_RESULTS_PER_PAGE = 10;

    #[Route(path: '/hx/list-nearby', name: 'app_hx_list_nearby', methods: ['GET'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $entityManager,
        LatLong $latLongUtils,
    ): Response {
        $renderArray = [];
        $postRepository = $entityManager->getRepository(Post::class);

        $latLng = $latLongUtils->fromString($request->query->get('latLng'));

        if (empty($latLng)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $offset = 0;
        if (!empty($request->query->get('offset'))) {
            $offset = (int) $request->query->get('offset');
            if ($offset < 0) {
                $offset = 0;
            }
        }

        $filter = [];

        $categoryRepository = $entityManager->getRepository(Category::class);
        if (!empty($request->query->get('category'))) {
            $categoryId = (int) $request->query->get('category');

            $category = $categoryRepository->findOneBy(['id' => $categoryId]);
            $filter['category'] = $category;
            $renderArray['category'] = $category;
        }

        $posts = $postRepository->findByDistance(
            $filter,
            $latLng,
            self::MAX_RESULTS_PER_PAGE,
            $offset,
            (null !== $user) ? $user->getId() : null,
        );

        $renderArray['posts'] = $posts;
        $renderArray['offset'] = $offset + self::MAX_RESULTS_PER_PAGE;
        $renderArray['latLng'] = $latLng;
        $renderArray['show_category'] = $categoryRepository->count() > 1;

        return $this->render('partials/hx/list_nearby_posts.html.twig', $renderArray);
    }
}
