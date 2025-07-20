<?php

namespace App\Controller\HTMX;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeletePostController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/hx/delete-post', name: 'app_hx_delete_post', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        if (empty($request->get('postId'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $postRepository = $entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $request->get('postId'),
        ]);

        if (empty($post)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('post', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($post->getUser()->getId() !== $user->getId() && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new Response('', Response::HTTP_OK);
    }
}
