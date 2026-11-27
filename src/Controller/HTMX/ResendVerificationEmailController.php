<?php

namespace App\Controller\HTMX;

use App\Entity\Email;
use DateTimeImmutable;
use App\Entity\Settings;
use App\Entity\User;
use App\Utilities\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResendVerificationEmailController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Mailer $mailer,
        private UrlGeneratorInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/hx/resend-verification-email', name: 'app_hx_resend_verification_email', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
    ): Response {
        if (null === $user) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('resend_verification_email', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($user->isEmailVerified()) {
            return new Response('', Response::BAD_REQUEST);
        }

        $settingsRepository = $this->entityManager->getRepository(Settings::class);

        $domain = getenv('SERVER_NAME');

        $email = new Email();
        $email->setToEmailAddress($user->getEmailAddress());
        $email->setFromEmailAddress($settingsRepository->getSettingByName('smtpFromEmailAddress')->getValue());
        $email->setSubject(
            $this->translator->trans('emails.verify_email_address.subject', [
                '%domain%' => $domain,
            ]),
        );
        $email->setBody(
            nl2br($this->translator->trans('emails.verify_email_address.body', [
                '%username%' => $user->getUsername(),
            ])) . '<p><a href="' . $domain . $this->router->generate('app_verify_user', [
                'userId' => $user->getId(),
                'verificationString' => $user->getEmailVerificationString(),
            ]) . '">' . $this->translator->trans('buttons.verify_email_address') . '</a>'
        );
        $email->setCreated(new DateTimeImmutable());

        $this->entityManager->persist($email);
        $this->entityManager->flush();

        return new Response('', Response::HTTP_OK);
    }
}
