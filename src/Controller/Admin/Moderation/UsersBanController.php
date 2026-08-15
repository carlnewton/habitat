<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\BlockedEmailAddress;
use App\Entity\ModerationLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
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
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->redirectToRoute('app_moderation_users');
        }

        $userIds = array_unique(array_map('intval', explode(',', $request->request->get('items'))));

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

        if (empty($request->request->get('delete'))) {
            return $this->render('admin/moderation/ban_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        $fieldErrors = $this->validate($request);

        if (!empty($fieldErrors)) {
            return $this->render('admin/moderation/ban_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
                'errors' => $fieldErrors,
                'values' => [
                    'reason' => $request->request->get('reason'),
                ],
            ]);
        }

        $usersBanned = false;
        $blockedEmailAddressRepository = $entityManager->getRepository(BlockedEmailAddress::class);
        foreach ($users as $user) {
            if (in_array('ROLE_MODERATOR', $user->getRoles())) {
                $this->addFlash('warning', $this->translator->trans(
                    'admin.moderation.users.validations.moderator_not_banned',
                    [
                        '%username%' => $user->getUsername(),
                    ]
                ));
                continue;
            }
            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('warning', $this->translator->trans(
                    'admin.moderation.users.validations.administrator_not_banned',
                    [
                        '%username%' => $user->getUsername(),
                    ]
                ));
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
                ->setAction($this->translator->trans('moderation_log.actions.ban', [
                    '%username%' => $user->getUsername(),
                    '%reason%' => $request->request->get('reason'),
                ]))
            ;
            $entityManager->persist($moderationLog);

            $entityManager->remove($user);
            $usersBanned = true;
        }

        if ($usersBanned) {
            $entityManager->flush();
            $this->addFlash('notice', $this->translator->trans('admin.moderation.users.banned'));
        }

        return $this->redirectToRoute('app_moderation_users');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (strlen($request->request->get('reason')) > 255) {
            $errors['reason'][] = $this->translator->trans(
                'admin.moderation.users.validations.reason_max_length',
                [
                    '%max_length%' => 255,
                ]
            );
        } elseif (empty(trim($request->request->get('reason')))) {
            $errors['reason'][] = $this->translator->trans('admin.moderation.users.validations.reason_required');
        }

        return $errors;
    }
}
