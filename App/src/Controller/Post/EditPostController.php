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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class EditPostController extends AbstractController
{
    protected array $categories = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/post/{id}/edit', name: 'app_edit_post', methods: ['GET', 'POST'])]
    public function index(
        int $id,
        #[CurrentUser] ?User $user,
        Request $request,
        LatLong $latLongUtils,
    ): Response {
        $postRepository = $this->entityManager->getRepository(Post::class);
        $post = $postRepository->findOneBy([
            'id' => $id,
        ]);

        if (!$post || $post->isRemoved()) {
            throw $this->createNotFoundException('The post does not exist');
        }

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

        $attachmentIds = [];
        foreach ($post->getAttachments() as $attachment) {
            $attachmentIds[] = $attachment->getId();
        }

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('edit', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->redirectToRoute('app_edit_post', ['id' => $post->getId()]);
            }

            foreach ($this->categories as $category) {
                if ($category->getId() === (int) $request->get('category')) {
                    $postCategory = $category;
                }
            }

            $post
                ->setTitle(trim($request->get('title')))
                ->setBody(trim($request->get('body')))
                ->setCategory($postCategory)
            ;

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('edit_post.html.twig', [
                    'errors' => $fieldErrors,
                    'post' => $post,
                    'categories' => $this->categories,
                    'attachmentIds' => implode(',', $attachmentIds),
                ]);
            }

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

            $postedAttachmentIds = [];
            if (!empty($request->get('attachmentIds'))) {
                $postedAttachmentIds = explode(',', $request->get('attachmentIds'));
            }

            $removedAttachmentIds = [];

            $originalAttachments = $post->getAttachments();
            $originalAttachmentIds = [];
            if (!empty($originalAttachments)) {
                foreach ($originalAttachments as $originalAttachment) {
                    $originalAttachmentIds[] = $originalAttachment->getId();
                    if (!in_array($originalAttachment->getId(), $postedAttachmentIds)) {
                        $removedAttachmentIds[] = $originalAttachment->getId();
                    }
                }
            }

            if (!empty($postedAttachmentIds)) {
                $newAttachmentIds = [];
                foreach ($postedAttachmentIds as $attachmentId) {
                    if (!in_array($attachmentId, $originalAttachmentIds)) {
                        $newAttachmentIds[] = $attachmentId;
                    }
                }
                $this->addAttachmentsToPost(implode(',', $newAttachmentIds), $post);
            }

            if (!empty($removedAttachmentIds)) {
                $attachmentRepository = $this->entityManager->getRepository(PostAttachment::class);
                foreach ($removedAttachmentIds as $removedAttachmentId) {
                    $removedAttachment = $attachmentRepository->findOneBy([
                        'id' => $removedAttachmentId
                    ]);
                    $removedAttachment->setPost(NULL);
                    $this->entityManager->persist($removedAttachment);
                }
            }

            $this->entityManager->flush();

            $this->addFlash(
                'notice',
                'The post has been updated'
            );

            return $this->redirectToRoute('app_view_post', ['id' => $post->getId()]);
        }

        return $this->render('edit_post.html.twig', [
            'post' => $post,
            'categories' => $this->categories,
            'attachmentIds' => implode(',', $attachmentIds),
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
