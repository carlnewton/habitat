<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\ModerationLog;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class PostsDeleteController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/posts/delete', name: 'app_moderation_posts_delete', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
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
            return $this->render('admin/moderation/delete_posts.html.twig', [
                'post_ids' => implode(',', $postIds),
                'posts' => $posts,
                'show_category' => true,
            ]);
        }

        foreach ($posts as $post) {
            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($this->getUser())
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.delete_post', [
                    '%post_title%' => $post->getTitle(),
                    '%username%' => $post->getUser()->getUsername(),
                ]));

            $entityManager->persist($moderationLog);

            $entityManager->remove($post);
        }
        $entityManager->flush();

        $this->addFlash('notice', 'Posts deleted');

        return $this->redirectToRoute('app_moderation_posts');
    }
}
