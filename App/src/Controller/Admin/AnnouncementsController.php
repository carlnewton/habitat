<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Entity\AnnouncementTypesEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class AnnouncementsController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/announcements', name: 'app_admin_announcements', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $announcementRepository = $entityManager->getRepository(Announcement::class);
        $announcement = $announcementRepository->findOneBy(['id' => 1]);

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    $this->translator->trans('fields.csrf_token.validations.invalid'),
                );

                return $this->render('admin/announcements.html.twig', [
                    'values' => [
                        'title' => $announcement ? $announcement->getTitle() : '',
                        'content' => $announcement ? $announcement->getContent() : '',
                        'collapse' => $announcement ? $announcement->isCollapse() : '',
                        'type' => $announcement ? $announcement->getType() : '',
                        'showDate' => $announcement && $announcement->getShowDate() ? $announcement->getShowDate()->format('Y-m-d\TH:i:s') : '',
                        'hideDate' => $announcement && $announcement->getHideDate() ? $announcement->getHideDate()->format('Y-m-d\TH:i:s') : '',
                    ],
                ]);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/announcements.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'title' => $request->get('title'),
                        'content' => $request->get('content'),
                        'collapse' => $request->get('collapse'),
                        'type' => AnnouncementTypesEnum::from($request->get('type')),
                        'showDate' => $request->get('showDate'),
                        'hideDate' => $request->get('hideDate'),
                    ],
                    'types' => AnnouncementTypesEnum::cases(),
                ]);
            }

            if (null === $announcement) {
                $announcement = new Announcement();
            }

            $showDate = null;
            if (!empty($request->get('showDate'))) {
                $showDate = (new \DateTime())->setTimestamp(strtotime($request->get('showDate')));
            }

            $hideDate = null;
            if (!empty($request->get('hideDate'))) {
                $hideDate = (new \DateTime())->setTimestamp(strtotime($request->get('hideDate')));
            }

            $announcement
                ->setTitle(trim($request->get('title')))
                ->setContent(trim($request->get('content')))
                ->setCollapse(boolval($request->get('collapse')))
                ->setType(AnnouncementTypesEnum::from($request->get('type')))
                ->setShowDate($showDate)
                ->setHideDate($hideDate)
            ;
            $entityManager->persist($announcement);
            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Announcement saved'
            );

            return $this->redirectToRoute('app_admin_announcements');
        }

        return $this->render('admin/announcements.html.twig', [
            'values' => [
                'title' => $announcement ? $announcement->getTitle() : '',
                'content' => $announcement ? $announcement->getContent() : '',
                'collapse' => $announcement ? $announcement->isCollapse() : '',
                'type' => $announcement ? $announcement->getType() : '',
                'showDate' => $announcement && $announcement->getShowDate() ? $announcement->getShowDate()->format('Y-m-d\TH:i:s') : '',
                'hideDate' => $announcement && $announcement->getHideDate() ? $announcement->getHideDate()->format('Y-m-d\TH:i:s') : '',
            ],
            'types' => AnnouncementTypesEnum::cases(),
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (Announcement::stripTags($request->get('content')) !== $request->get('content')) {
            $errors['content'][] = $this->translator->trans('admin.announcements.validations.content.disallowed_html_tags');
        }

        if (is_null($request->get('type')) || empty(AnnouncementTypesEnum::from($request->get('type')))) {
            $errors['type'][] = $this->translator->trans('fields.type.validations.invalid');
        }

        $showTimestamp = null;
        if (!empty($request->get('showDate'))) {
            $showTimestamp = strtotime($request->get('showDate'));
            if (false === $showTimestamp) {
                $errors['showDate'][] = $this->translator->trans('fields.show_date.validations.invalid');
            }
        }

        $hideTimestamp = null;
        if (!empty($request->get('hideDate'))) {
            $hideTimestamp = strtotime($request->get('hideDate'));
            if (false === $hideTimestamp) {
                $errors['hideDate'][] = $this->translator->trans('fields.hide_date.validations.invalid');
            }
        }

        if (null !== $showTimestamp && null !== $hideTimestamp && $showTimestamp >= $hideTimestamp) {
            $errors['showDate'][] = $this->translator->trans('fields.show_date.validations.gte_hide_date');
            $errors['hideDate'][] = $this->translator->trans('fields.hide_date.validations.lt_hide_date');
        }

        return $errors;
    }
}
