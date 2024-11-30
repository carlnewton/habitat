<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use App\Utilities\AmazonS3;
use App\Utilities\Mailer;
use Aws\S3\Exception\S3Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class MailSettingsController extends AbstractController
{
    #[Route(path: '/admin/mail', name: 'app_admin_mail', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        Mailer $mailer
    ): Response {
        $settingsRepository = $entityManager->getRepository(Settings::class);

        $smtpUsername = $settingsRepository->getSettingByName('smtpUsername');
        $smtpServer = $settingsRepository->getSettingByName('smtpServer');
        $smtpPort = $settingsRepository->getSettingByName('smtpPort');
        $smtpFromEmailAddress = $settingsRepository->getSettingByName('smtpFromEmailAddress');

        if ('POST' !== $request->getMethod()) {
            return $this->render('admin/mail.html.twig', [
                'values' => [
                    'smtpUsername' => $smtpUsername->getValue(),
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
                'Something went wrong, please try again.'
            );

            return $this->render('admin/mail.html.twig', [
                'values' => [
                    'smtpUsername' => $smtpUsername->getValue(),
                    'smtpServer' => $smtpServer->getValue(),
                    'smtpPort' => $smtpPort->getValue(),
                    'smtpFromEmailAddress' => $smtpFromEmailAddress->getValue(),
                ],
            ]);
        }

        $savedSmtpPassword = $settingsRepository->getSettingByName('smtpPassword');
        $fieldErrors = $this->validateRequest($request, $savedSmtpPassword);

        if (!empty($fieldErrors)) {
            return $this->render('admin/mail.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'smtpUsername' => $request->get('smtpUsername'),
                    'smtpServer' => $request->get('smtpServer'),
                    'smtpPort' => $request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->get('smtpFromEmailAddress'),
                ],
            ]);
        }

        if (!empty($request->get('smtpToEmailAddress'))) {
            $password = $savedSmtpPassword->getEncryptedValue();
            if (!empty($request->get('smtpPassword'))) {
                $password = $request->get('smtpPassword');
            }
            $mailException = null;
            try {
                $mailer->sendTest(
                    $request->get('smtpUsername'),
                    $password,
                    $request->get('smtpServer'),
                    (int) $request->get('smtpPort'),
                    $request->get('smtpToEmailAddress'),
                    $request->get('smtpFromEmailAddress'),
                );
            } catch (TransportExceptionInterface $e) {
                $mailException = $e->getMessage();
            }

            if (!is_null($mailException)) {
                return $this->render('admin/mail.html.twig', [
                    'email_sent_exception' => $mailException,
                    'values' => [
                        'smtpUsername' => $smtpUsername->getValue(),
                        'smtpServer' => $smtpServer->getValue(),
                        'smtpPort' => $smtpPort->getValue(),
                        'smtpFromEmailAddress' => $smtpFromEmailAddress->getValue(),
                    ],
                ]);
            }

            $this->addFlash(
                'notice',
                'A test email has been sent to ' . $request->get('smtpToEmailAddress') . ' and no issues have been ' . 
                'reported. If you have not received it, check the settings here and try again.'
            );
        }

        $smtpUsername->setValue($request->get('smtpUsername'));
        $entityManager->persist($smtpUsername);

        if (!empty($request->get('smtpPassword'))) {
            $smtpPassword = $settingsRepository->getSettingByName('smtpPassword');
            $smtpPassword->setEncryptedValue($request->get('smtpPassword'));
            $entityManager->persist($smtpPassword);
        }

        $smtpServer->setValue($request->get('smtpServer'));
        $entityManager->persist($smtpServer);

        $smtpPort->setValue($request->get('smtpPort'));
        $entityManager->persist($smtpPort);

        $smtpFromEmailAddress->setValue($request->get('smtpFromEmailAddress'));
        $entityManager->persist($smtpFromEmailAddress);

        $entityManager->flush();

        $this->addFlash(
            'notice',
            'Mail settings saved'
        );

        return $this->redirectToRoute('app_admin_mail');
    }

    private function validateRequest(Request $request, Settings $smtpPassword): array
    {
        $errors = [];

        if (empty($request->get('smtpUsername'))) {
            $errors['smtpUsername'][] = 'You must enter an SMTP username';
        }

        if (empty($_ENV['ENCRYPTION_KEY'])) {
            $errors['smtpPassword'][] = 'The password key cannot be saved unless an ENCRYPTION_KEY environment variable is set';
        }

        if (empty($request->get('smtpServer'))) {
            $errors['smtpServer'][] = 'You must enter an SMTP server';
        }

        if (empty($request->get('smtpPort')) || !is_numeric($request->get('smtpPort'))) {
            $errors['smtpPort'][] = 'You must enter a valid port number';
        }

        if (empty($request->get('smtpFromEmailAddress')) || !filter_var($request->get('smtpFromEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpFromEmailAddress'][] = 'You must enter a valid sender email address';
        }

        if (!empty($request->get('smtpToEmailAddress')) && !filter_var($request->get('smtpToEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpToEmailAddress'][] = 'You must enter a valid recipient email address';
        }

        return $errors;
    }
}
