<?php

namespace App\Controller\Post;

use App\Entity\Comment;
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
    #[Route(path: '/post/{id}', name: 'app_view_post', methods: ['GET', 'POST'])]
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

        if ($request->getMethod() === 'POST') {
            if ($user === null) {
                return $this->redirectToRoute('app_login');
            }

            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('comment', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('post.html.twig');
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('view_post.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'comment' => $request->get('comment'),
                    ]
                ]);
            }

            $comment = new Comment();
            $comment
                ->setBody(trim($request->get('comment')))
                ->setPosted(new DateTimeImmutable())
                ->setPost($post)
                ->setUser($user)
            ;
            
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Your comment has been added'
            );

            return $this->redirectToRoute('app_view_post', ['id' => $post->getId()]);
        }

        return $this->render('view_post.html.twig', [
            'post' => $post,
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
