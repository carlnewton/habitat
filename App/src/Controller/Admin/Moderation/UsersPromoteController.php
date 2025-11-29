<?php

namespace App\Controller\Admin\Moderation;

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
class UsersPromoteController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/users/promote', name: 'app_moderation_users_promote', methods: ['POST'], priority: 2)]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
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
                $this->translator->trans('admin.moderation.users.validations.users_not_found'),
            );

            return $this->redirectToRoute('app_moderation_users');
        }

        if (empty($request->get('promote'))) {
            return $this->render('admin/moderation/promote_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        $usersPromoted = false;
        foreach ($users as $user) {
            if (in_array('ROLE_MODERATOR', $user->getRoles())) {
                $this->addFlash('warning', $user->getUsername() . ' could not be promoted because they are already a moderator.');
                continue;
            }

            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('warning', $user->getUsername() . ' could not be promoted because they are the administrator.');
                continue;
            }

            $user->setRoles(['ROLE_MODERATOR']);
            $entityManager->persist($user);

            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($this->getUser())
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.promote', [
                    '%username%' => $user->getUsername(),
                ]))
            ;
            $entityManager->persist($moderationLog);

            $usersPromoted = true;
        }

        if ($usersPromoted) {
            $entityManager->flush();
            $this->addFlash('notice', 'Users promoted');
        }

        return $this->redirectToRoute('app_moderation_users');
    }
}
