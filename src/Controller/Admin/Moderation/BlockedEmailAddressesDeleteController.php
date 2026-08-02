<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\BlockedEmailAddress;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class BlockedEmailAddressesDeleteController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/blocked-email-addresses/unblock', name: 'app_moderation_blocked_email_addresses_unblock', methods: ['POST'], priority: 2)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->redirectToRoute('app_moderation_blocked_email_addresses');
        }

        $blockedEmailAddressIds = array_unique(array_map('intval', explode(',', $request->request->get('items'))));

        $blockedEmailAddressRepository = $entityManager->getRepository(BlockedEmailAddress::class);

        $blockedEmailAddresses = $blockedEmailAddressRepository->findBy([
            'id' => $blockedEmailAddressIds,
        ]);

        if (empty($blockedEmailAddresses)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('admin.moderation.blocked_email_addresses.not_found'),
            );

            return $this->redirectToRoute('app_moderation_blocked_email_addresses');
        }

        if (empty($request->request->get('delete'))) {
            return $this->render('admin/moderation/unblock_email_addresses.html.twig', [
                'blocked_email_address_ids' => implode(',', $blockedEmailAddressIds),
                'blocked_email_addresses' => $blockedEmailAddresses,
            ]);
        }

        foreach ($blockedEmailAddresses as $blockedEmailAddress) {
            $entityManager->remove($blockedEmailAddress);
        }

        $entityManager->flush();
        $this->addFlash('notice', $this->translator->trans('admin.moderation.blocked_email_addresses.unblocked'));

        return $this->redirectToRoute('app_moderation_blocked_email_addresses');
    }
}
