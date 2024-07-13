<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class PostsRemovalController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    #[Route(path: '/admin/moderation/posts/remove', name: 'app_moderation_posts_remove', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->redirectToRoute('app_moderation_posts');
        }

        $postIds = array_unique(array_map('intval', explode(',', $request->get('items'))));

        $postRepository = $entityManager->getRepository(Post::class);

        $posts = $postRepository->findBy(
            [
                'id' => $postIds,
                'removed' => false,
            ]
        );

        if (empty($posts)) {
            $this->addFlash(
                'warning',
                'The posts could not be found.'
            );

            return $this->redirectToRoute('app_moderation_posts');
        }

        if (empty($request->get('delete'))) {
            return $this->render('admin/moderation/remove_posts.html.twig', [
                'post_ids' => implode(',', $postIds),
                'posts' => $posts,
            ]);
        }

        foreach ($posts as $post) {
            $post->setRemoved(true);
            $entityManager->persist($post);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Posts removed');

        return $this->redirectToRoute('app_moderation_posts');
    }
}
