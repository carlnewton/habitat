<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\BlockedEmailAddress;
use App\Entity\ModerationLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersBanController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/users/ban', name: 'app_moderation_users_ban', methods: ['POST'], priority: 2)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->redirectToRoute('app_moderation_users');
        }

        $userIds = array_unique(array_map('intval', explode(',', $request->get('items'))));

        $userRepository = $entityManager->getRepository(User::class);

        $users = $userRepository->findBy([
            'id' => $userIds,
        ]);

        if (empty($users)) {
            $this->addFlash(
                'warning',
                'The users could not be found.'
            );

            return $this->redirectToRoute('app_moderation_users');
        }

        if (empty($request->get('delete'))) {
            return $this->render('admin/moderation/ban_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        $usersBanned = false;
        $blockedEmailAddressRepository = $entityManager->getRepository(BlockedEmailAddress::class);
        foreach ($users as $user) {
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('warning', $user->getUsername() . ' could not be banned because they are an administrator.');
                continue;
            }

            $blockedEmailAddress = $blockedEmailAddressRepository->findOneBy([
                'email_address' => $user->getEmailAddress(),
            ]);

            if (empty($blockedEmailAddress)) {
                $blockedEmailAddress = new BlockedEmailAddress();
                $blockedEmailAddress->setEmailAddress($user->getEmailAddress());
                $entityManager->persist($blockedEmailAddress);
            }

            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($this->getUser())
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.ban', ['%username%' => $user->getUsername()]))
            ;
            $entityManager->persist($moderationLog);

            $entityManager->remove($user);
            $usersBanned = true;
        }

        if ($usersBanned) {
            $entityManager->flush();
            $this->addFlash('notice', 'Users banned');
        }

        return $this->redirectToRoute('app_moderation_users');
    }
}
