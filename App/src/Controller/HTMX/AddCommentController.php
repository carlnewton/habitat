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
use Symfony\Contracts\Translation\TranslatorInterface;

class AddCommentController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

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

        if ($user->isFrozen()) {
            $freezeLog = $user->getFrozenLog();

            return $this->render('partials/hx/alert.html.twig', [
                'type' => 'danger',
                'message' => $this->translator->trans('flash_messages.account_frozen', [
                    '%unfreeze_datetime%' => $freezeLog->getUnfreezeDate()->format('F jS Y H:i'),
                    '%reason%' => $freezeLog->getReason(),
                ]),
            ]);

            return $this->redirectToRoute('app_index_index');
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
