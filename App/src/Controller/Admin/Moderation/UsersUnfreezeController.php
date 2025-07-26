<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersUnfreezeController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/users/unfreeze', name: 'app_moderation_users_unfreeze', methods: ['POST'], priority: 2)]
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

        if (empty($request->get('unfreeze'))) {
            return $this->render('admin/moderation/unfreeze_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        $usersUnfrozen = false;
        foreach ($users as $user) {
            if (!$user->isFrozen()) {
                $this->addFlash('warning', $user->getUsername() . ' could not be unfrozen because they are not frozen.');
                continue;
            }

            $freezeLog = $user->getFrozenLog();
            $freezeLog->setUnfreezeDate(new \DateTimeImmutable());
            $entityManager->persist($freezeLog);
            $usersUnfrozen = true;
        }

        if ($usersUnfrozen) {
            $entityManager->flush();
            $this->addFlash('notice', 'Users unfrozen');
        }

        return $this->redirectToRoute('app_moderation_users');
    }
}
