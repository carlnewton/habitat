<?php

namespace App\Controller\Post;

use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use App\Entity\Post;
use App\Entity\PostAttachment;
use App\Entity\User;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreatePostController extends AbstractController
{
    protected array $categories = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/post', name: 'app_create_post', methods: ['GET', 'POST'])]
    public function index(
        #[CurrentUser] ?User $user,
        Request $request,
        LatLong $latLongUtils,
    ): Response {
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isFrozen()) {
            $freezeLog = $user->getFrozenLog();
            $this->addFlash(
                'warning',
                $this->translator->trans('flash_messages.account_frozen', [
                    '%unfreeze_datetime%' => $freezeLog->getUnfreezeDate()->format('F jS Y H:i'),
                    '%reason%' => $freezeLog->getReason(),
                ])
            );

            return $this->redirectToRoute('app_index_index');
        }

        $postRepository = $this->entityManager->getRepository(Post::class);
        $categoryRepository = $this->entityManager->getRepository(Category::class);
        $this->categories = $categoryRepository->findBy(
            [
                'allow_posting' => true,
            ],
            [
                'weight' => 'asc',
                'name' => 'asc',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('post', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('create_post.html.twig');
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('create_post.html.twig', [
                    'errors' => $fieldErrors,
                    'categories' => $this->categories,
                    'values' => [
                        'title' => $request->get('title'),
                        'body' => $request->get('body'),
                        'locationLatLng' => $request->get('locationLatLng'),
                        'attachmentIds' => $request->get('attachmentIds'),
                        'category' => $request->get('category'),
                    ],
                ]);
            }

            foreach ($this->categories as $category) {
                if ($category->getId() === (int) $request->get('category')) {
                    $postCategory = $category;
                }
            }
            $post = new Post();
            $post
                ->setTitle(trim($request->get('title')))
                ->setBody(trim($request->get('body')))
                ->setPosted(new \DateTime())
                ->setUser($user)
                ->setCategory($postCategory)
            ;

            if (
                in_array($postCategory->getLocation(), [
                    CategoryLocationOptionsEnum::REQUIRED,
                    CategoryLocationOptionsEnum::OPTIONAL,
                ]) && !empty($request->get('locationLatLng'))
            ) {
                $latLng = $latLongUtils->fromString($request->get('locationLatLng'));
                $post->setLatitude($latLng->latitude);
                $post->setLongitude($latLng->longitude);
            }

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            if (!empty($request->get('attachmentIds'))) {
                $this->addAttachmentsToPost($request->get('attachmentIds'), $post);
            }

            $this->addFlash(
                'notice',
                'Your post has been submitted'
            );

            return $this->redirectToRoute('app_view_post', ['id' => $post->getId()]);
        }

        return $this->render('create_post.html.twig', [
            'categories' => $this->categories,
        ]);
    }

    protected function addAttachmentsToPost(string $attachmentIdString, Post $post): void
    {
        if (empty($attachmentIdString)) {
            return;
        }

        $attachmentIds = explode(',', $attachmentIdString);

        if (empty($attachmentIds)) {
            return;
        }

        $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);

        $attachmentIdArray = [];
        foreach ($attachmentIds as $attachmentId) {
            if (!ctype_digit($attachmentId)) {
                continue;
            }

            $attachmentIdArray[] = (int) $attachmentId;
        }

        $attachmentIdArray = array_unique($attachmentIdArray);

        $attachmentEntities = $attachmentRepository->findOrphanedById($attachmentIdArray);

        if (empty($attachmentEntities)) {
            return;
        }

        foreach ($attachmentEntities as $attachmentEntity) {
            $attachmentEntity->setPost($post);
            $this->entityManager->persist($attachmentEntity);
        }

        $this->entityManager->flush();
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->get('title')) > Post::TITLE_MAX_LENGTH) {
            $errors['title'][] = 'The title must be a maximum of ' . Post::TITLE_MAX_LENGTH . ' characters';
        } elseif (empty(trim($request->get('title')))) {
            $errors['title'][] = 'The title cannot be empty';
        }

        if (empty($request->get('category'))) {
            $errors['category'][] = 'You must choose a category';
        } else {
            $foundCategory = null;
            foreach ($this->categories as $category) {
                if ($category->getId() === (int) $request->get('category')) {
                    $foundCategory = $category;
                    break;
                }
            }

            if (is_null($foundCategory)) {
                $errors['category'][] = 'You must choose a category';
            } elseif (
                (
                    CategoryLocationOptionsEnum::REQUIRED === $foundCategory->getLocation()
                    && (
                        empty($request->get('locationLatLng'))
                        || !LatLong::isValidLatLong($request->get('locationLatLng'))
                    )
                ) || (
                    CategoryLocationOptionsEnum::OPTIONAL === $foundCategory->getLocation()
                    && !empty($request->get('locationLatLng'))
                    && !LatLong::isValidLatLong($request->get('locationLatLng'))
                )
            ) {
                $errors['location'][] = 'You must choose a location';
            }
        }

        return $errors;
    }
}
