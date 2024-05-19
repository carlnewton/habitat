<?php

namespace App\Controller\Post;

use DateTime;
use App\Entity\User;
use App\Entity\Post;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class CreatePostController extends AbstractController
{
    #[Route(path: '/post', name: 'app_create_post', methods: ['GET', 'POST'])]
    public function index(
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $postRepository = $entityManager->getRepository(Post::class);

        if ($request->getMethod() === 'POST') {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('post', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('post.html.twig');
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('post.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'title' => $request->get('title'),
                        'body' => $request->get('body'),
                        'locationLatLng' => $request->get('locationLatLng')
                    ]
                ]);
            }

            $post = new Post();
            $post
                ->setTitle(trim($request->get('title')))
                ->setBody(trim($request->get('body')))
                ->setLocation($request->get('locationLatLng'))
                ->setPosted(new DateTime())
                ->setUser($user)
            ;
            
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Your post has been submitted'
            );

            return $this->redirectToRoute('app_view_post', ['id' => $post->getId()]);
        }

        return $this->render('post.html.twig');
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->get('title')) > Post::TITLE_MAX_LENGTH) {
            $errors['title'][] = 'The title must be a maximum of ' . Post::TITLE_MAX_LENGTH . ' characters';
        } else if (empty(trim($request->get('title')))) {
            $errors['title'][] = 'The title cannot be empty';
        }

        if (
            empty($request->get('locationLatLng')) ||
            !LatLong::isValidLatLong($request->get('locationLatLng'))
        ) {
            $errors['location'][] = 'You must choose a valid location';
        }

        return $errors;
    }
}
