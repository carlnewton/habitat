<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use App\Utilities\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class MailSettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/mail', name: 'app_admin_mail', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
    ): Response {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);

        $smtpUsername = $settingsRepository->getSettingByName('smtpUsername');
        $smtpPassword = $settingsRepository->getSettingByName('smtpPassword');
        $smtpServer = $settingsRepository->getSettingByName('smtpServer');
        $smtpPort = $settingsRepository->getSettingByName('smtpPort');
        $smtpFromEmailAddress = $settingsRepository->getSettingByName('smtpFromEmailAddress');

        if ('POST' !== $request->getMethod()) {
            return $this->render('admin/mail.html.twig', [
                'values' => [
                    'smtpUsername' => $smtpUsername->getValue(),
                    'smtpPassword' => $smtpPassword->getEncryptedValue(),
                    'smtpServer' => $smtpServer->getValue(),
                    'smtpPort' => $smtpPort->getValue(),
                    'smtpFromEmailAddress' => $smtpFromEmailAddress->getValue(),
                ],
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('admin/mail.html.twig', [
                'values' => [
                    'smtpUsername' => $smtpUsername->getValue(),
                    'smtpPassword' => $smtpPassword->getEncryptedValue(),
                    'smtpServer' => $smtpServer->getValue(),
                    'smtpPort' => $smtpPort->getValue(),
                    'smtpFromEmailAddress' => $smtpFromEmailAddress->getValue(),
                ],
            ]);
        }

        $fieldErrors = $this->validateRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('admin/mail.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'smtpUsername' => $request->request->get('smtpUsername'),
                    'smtpPassword' => $request->request->get('smtpPassword'),
                    'smtpServer' => $request->request->get('smtpServer'),
                    'smtpPort' => $request->request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->request->get('smtpFromEmailAddress'),
                ],
            ]);
        }

        if (!is_null($request->request->get('actionTest'))) {
            $mailException = null;
            try {
                $this->mailer->sendTest(
                    $request->request->get('smtpUsername'),
                    $request->request->get('smtpPassword'),
                    $request->request->get('smtpServer'),
                    (int) $request->request->get('smtpPort'),
                    $request->request->get('smtpToEmailAddress'),
                    $request->request->get('smtpFromEmailAddress'),
                );
            } catch (TransportExceptionInterface $e) {
                $mailException = $e->getMessage();
            }

            if (!is_null($mailException)) {
                return $this->render('admin/mail.html.twig', [
                    'email_sent_exception' => $mailException,
                    'values' => [
                        'smtpUsername' => $smtpUsername->getValue(),
                        'smtpPassword' => $smtpPassword->getEncryptedValue(),
                        'smtpServer' => $smtpServer->getValue(),
                        'smtpPort' => $smtpPort->getValue(),
                        'smtpFromEmailAddress' => $smtpFromEmailAddress->getValue(),
                    ],
                ]);
            }

            $this->addFlash(
                'notice',
                $this->translator->trans(
                    'setup.configure_mail.test_email_sent',
                    [
                        '%email_address%' => $request->request->get('smtpToEmailAddress'),
                    ]
                )
            );
        }

        $smtpUsername->setValue($request->request->get('smtpUsername'));
        $this->entityManager->persist($smtpUsername);

        $smtpPassword = $settingsRepository->getSettingByName('smtpPassword');
        $smtpPassword->setEncryptedValue($request->request->get('smtpPassword'));
        $this->entityManager->persist($smtpPassword);

        $smtpServer->setValue($request->request->get('smtpServer'));
        $this->entityManager->persist($smtpServer);

        $smtpPort->setValue($request->request->get('smtpPort'));
        $this->entityManager->persist($smtpPort);

        $smtpFromEmailAddress->setValue($request->request->get('smtpFromEmailAddress'));
        $this->entityManager->persist($smtpFromEmailAddress);

        $this->entityManager->flush();

        $this->addFlash(
            'notice',
            'Mail settings saved'
        );

        return $this->redirectToRoute('app_admin_mail');
    }

    private function validateRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->request->get('smtpServer'))) {
            $errors['smtpServer'][] = $this->translator->trans('fields.smtp_server.validations.empty');
        }

        if (empty($request->request->get('smtpPort')) || !is_numeric($request->request->get('smtpPort'))) {
            $errors['smtpPort'][] = $this->translator->trans('fields.smtp_port.validations.invalid');
        }

        if (empty($request->request->get('smtpFromEmailAddress')) || !filter_var($request->request->get('smtpFromEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpFromEmailAddress'][] = $this->translator->trans('fields.sender_email_address.validations.invalid');
        }

        if (!is_null($request->request->get('actionTest')) && !filter_var($request->request->get('smtpToEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpToEmailAddress'][] = $this->translator->trans('fields.test_recipient_email_address.validations.invalid');
        }

        return $errors;
    }
}
