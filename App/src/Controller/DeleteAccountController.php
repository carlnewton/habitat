<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteAccountController extends AbstractController
{
    #[Route(path: '/settings/delete-account', name: 'app_delete_account')]
    public function deleteAccount(
        Request $request,
        TranslatorInterface $translator,
        Security $security,
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User $user,
    ): Response {
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        if ('GET' === $request->getMethod()) {
            return $this->render('security/delete_account.html.twig');
        }

        $submittedToken = $request->getPayload()->get('_csrf_token');
        if (!$this->isCsrfTokenValid('delete_account', $submittedToken)) {
            $this->addFlash(
                'warning',
                $translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('security/delete_account.html.twig');
        }

        if (trim($request->get('email_address')) !== $user->getEmailAddress()) {
            return $this->render('security/delete_account.html.twig', [
                'error' => $translator->trans('fields.email_address.validations.non_matching_email_address')
            ]);
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return $this->render('security/delete_account.html.twig', [
                'error' => $translator->trans('user_settings.delete_account.validations.admin_account')
            ]);
        }

        $security->logout(false);

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash(
            'notice',
            $translator->trans('user_settings.delete_account.success')
        );

        return $this->redirectToRoute('app_index_index');
    }
}
