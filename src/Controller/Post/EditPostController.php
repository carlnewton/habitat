<?php

namespace App\Controller\Post;

use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use App\Entity\ModerationLog;
use App\Entity\Post;
use App\Entity\PostAttachment;
use App\Entity\Settings;
use App\Entity\User;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * NOTE: If ever opening this up to generic users, ensure not to allow frozen accounts to edit posts.
 */
#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class EditPostController extends AbstractController
{
    protected array $categories = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
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

        if (!$post) {
            throw $this->createNotFoundException($this->translator->trans('post.does_not_exist'));
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
                    $this->translator->trans('fields.csrf_token.validations.invalid'),
                );

                return $this->redirectToRoute('app_edit_post', ['id' => $post->getId()]);
            }

            foreach ($this->categories as $category) {
                if ($category->getId() === (int) $request->request->get('category')) {
                    $postCategory = $category;
                }
            }

            $post
                ->setTitle(trim($request->request->get('title')))
                ->setBody(trim($request->request->get('body')))
                ->setCategory($postCategory)
            ;

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('edit_post.html.twig', [
                    'errors' => $fieldErrors,
                    'post' => $post,
                    'categories' => $this->categories,
                    'attachmentIds' => implode(',', $attachmentIds),
                    'reason' => $request->request->get('reason'),
                ]);
            }

            if (
                in_array($postCategory->getLocation(), [
                    CategoryLocationOptionsEnum::REQUIRED,
                    CategoryLocationOptionsEnum::OPTIONAL,
                ]) && !empty($request->request->get('locationLatLng'))
            ) {
                $latLng = $latLongUtils->fromString($request->request->get('locationLatLng'));
                $post->setLatitude($latLng->latitude);
                $post->setLongitude($latLng->longitude);
            }

            $this->entityManager->persist($post);

            $postedAttachmentIds = [];
            if (!empty($request->request->get('attachmentIds'))) {
                $postedAttachmentIds = explode(',', $request->request->get('attachmentIds'));
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
                        'id' => $removedAttachmentId,
                    ]);
                    $removedAttachment->setPost(null);
                    $this->entityManager->persist($removedAttachment);
                }
            }

            // Edit functionality is currently only available to admins, but this might change, so I'm putting this
            // extra check in now just in case it's later forgotten.
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $moderationLog = new ModerationLog();
                $moderationLog
                    ->setUser($user)
                    ->setDate(new \DateTimeImmutable())
                    ->setAction($this->translator->trans('moderation_log.actions.edit_post', [
                        '%post_title%' => $post->getTitle(),
                        '%username%' => $post->getUser()->getUsername(),
                        '%reason%' => $request->request->get('reason'),
                    ]));

                $this->entityManager->persist($moderationLog);
            }

            $this->entityManager->flush();

            $this->addFlash('notice', $this->translator->trans('post.updated'));

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

        if (mb_strlen($request->request->get('title')) > Post::TITLE_MAX_LENGTH) {
            $errors['title'][] = $this->translator->trans('fields.title.validations.max_length', ['%max_length%' => Post::TITLE_MAX_LENGTH]);
        } elseif (empty(trim($request->request->get('title')))) {
            $errors['title'][] = $this->translator->trans('fields.title.validations.empty');
        }

        if (strlen($request->request->get('reason')) > 255) {
            $errors['reason'][] = $this->translator->trans('fields.reason.validations.max_length', ['%max_length%' => 255]);
        } elseif (empty(trim($request->request->get('reason')))) {
            $errors['reason'][] = $this->translator->trans('fields.reason.validations.required');
        }

        if (empty($request->request->get('category'))) {
            $errors['category'][] = $this->translator->trans('fields.category.validations.required');
        } else {
            $foundCategory = null;
            foreach ($this->categories as $category) {
                if ($category->getId() === (int) $request->request->get('category')) {
                    $foundCategory = $category;
                    break;
                }
            }

            $settingsRepository = $this->entityManager->getRepository(Settings::class);
            $radiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');
            $latLngSetting = $settingsRepository->getSettingByName('locationLatLng');
            if (is_null($foundCategory)) {
                $errors['category'][] = $this->translator->trans('fields.category.validations.required');
            } elseif (
                (
                    CategoryLocationOptionsEnum::REQUIRED === $foundCategory->getLocation()
                    && (
                        empty($request->request->get('locationLatLng'))
                        || !LatLong::isValidLatLong(
                            $request->request->get('locationLatLng'),
                            $latLngSetting->getValue(),
                            $radiusSetting->getValue()
                        )
                    )
                ) || (
                    CategoryLocationOptionsEnum::OPTIONAL === $foundCategory->getLocation()
                    && !empty($request->request->get('locationLatLng'))
                    && !LatLong::isValidLatLong(
                        $request->request->get('locationLatLng'),
                        $latLngSetting->getValue(),
                        $radiusSetting->getValue()
                    )
                )
            ) {
                $errors['location'][] = $this->translator->trans('fields.location.validations.required');
            }
        }

        return $errors;
    }
}
