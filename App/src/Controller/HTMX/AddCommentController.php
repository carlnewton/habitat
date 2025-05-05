<?php

namespace App\Controller\HTMX;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Event\AfterCommentPostedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AddCommentController extends AbstractController
{
    #[Route(path: '/hx/add-comment', name: 'app_hx_add_comment', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
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
        if (!$this->isCsrfTokenValid('comment', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $fieldErrors = $this->validateRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('partials/hx/post_comment.html.twig', [
                'post' => $post,
                'errors' => $fieldErrors,
                'values' => [
                    'comment' => $request->get('comment'),
                ],
            ]);
        }

        $comment = new Comment();
        $comment
            ->setBody(trim($request->get('comment')))
            ->setPosted(new \DateTimeImmutable())
            ->setPost($post)
            ->setUser($user)
        ;

        $entityManager->persist($comment);
        $entityManager->flush();

        $event = new AfterCommentPostedEvent($comment);
        $eventDispatcher->dispatch($event, AfterCommentPostedEvent::NAME);

        return $this->render('partials/hx/post_comment.html.twig', [
            'post' => $post,
            'comment' => $comment,
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (empty(trim($request->get('comment')))) {
            $errors['comment'][] = 'The comment cannot be empty';
        }

        return $errors;
    }
}
