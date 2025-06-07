<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\ModerationLog;
use App\Entity\User;
use App\Entity\UserFreezeLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class UsersFreezeController extends AbstractController
{
    private const FREEZE_INTERVALS = [
        'minutes',
        'hours',
        'days',
    ];

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/users/freeze', name: 'app_moderation_users_freeze', methods: ['POST'], priority: 2)]
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

        if (empty($request->get('freeze'))) {
            return $this->render('admin/moderation/freeze_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
                'intervals' => self::FREEZE_INTERVALS,
            ]);
        }

        $fieldErrors = $this->validate($request);

        if (!empty($fieldErrors)) {
            return $this->render('admin/moderation/freeze_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
                'intervals' => self::FREEZE_INTERVALS,
                'errors' => $fieldErrors,
                'values' => [
                    'freezeForValue' => $request->get('freezeForValue'),
                    'freezeForInterval' => $request->get('freezeForInterval'),
                    'reason' => $request->get('reason'),
                ],
            ]);
        }

        $usersFrozen = false;
        foreach ($users as $user) {
            if ($user->isFrozen()) {
                $this->addFlash('warning', $user->getUsername() . ' could not be frozen because they are already frozen.');
                continue;
            }

            if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $this->addFlash('warning', $user->getUsername() . ' could not be frozen because they are an administrator.');
                continue;
            }

            $moderationLog = new ModerationLog();
            $moderationLog
                ->setUser($this->getUser())
                ->setDate(new \DateTimeImmutable())
                ->setAction($this->translator->trans('moderation_log.actions.freeze', [
                    '%username%' => $user->getUsername(),
                    '%freeze_value%' => $request->get('freezeForValue'),
                    '%freeze_interval%' => $request->get('freezeForInterval'),
                    '%reason%' => $request->get('reason'),
                ]))
            ;
            $entityManager->persist($moderationLog);

            $freezeLog = new UserFreezeLog();
            $freezeLog
                ->setUser($user)
                ->setFreezeDate(new \DateTimeImmutable())
                ->setUnfreezeDate((new \DateTimeImmutable())->modify(
                    '+' . $request->get('freezeForValue') .
                    ' ' . $request->get('freezeForInterval')
                ))
                ->setReason($request->get('reason'))
            ;
            $entityManager->persist($freezeLog);
            $usersFrozen = true;
        }

        if ($usersFrozen) {
            $entityManager->flush();
            $this->addFlash('notice', 'Users frozen');
        }

        return $this->redirectToRoute('app_moderation_users');
    }

    private function validate(Request $request): array
    {
        $errors = [];

        if (
            (int) $request->get('freezeForValue') < 1
            || !in_array($request->get('freezeForInterval'), self::FREEZE_INTERVALS)
        ) {
            $errors['freezeFor'][] = 'You must enter a valid freeze time';
        }

        if (strlen($request->get('reason')) > 255) {
            $errors['reason'][] = 'The value of this field must be a maximum of 255 characters';
        } elseif (empty(trim($request->get('reason')))) {
            $errors['reason'][] = 'This is a required field';
        }

        return $errors;
    }
}
