<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN")'), statusCode: 403, exceptionCode: 10010)]
class UsersVerifyController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/moderation/users/verify', name: 'app_moderation_users_verify', methods: ['POST'], priority: 2)]
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

        if (empty($request->request->get('verify'))) {
            return $this->render('admin/moderation/verify_users.html.twig', [
                'user_ids' => implode(',', $userIds),
                'users' => $users,
            ]);
        }

        $usersVerified = false;
        foreach ($users as $user) {
            if ($user->isEmailVerified()) {
                $this->addFlash(
                    'warning',
                    $this->translator->trans('admin.actions.verify.messages.already_verified',
                        [
                            '%username%' => $user->getUsername(),
                        ]
                    )
                );
                continue;
            }

            $user->setEmailVerified(true);
            $user->setEmailVerificationString(null);
            $entityManager->persist($user);

            $usersVerified = true;
        }

        if ($usersVerified) {
            $entityManager->flush();
            $this->addFlash('notice', $this->translator->trans('admin.actions.verify.messages.success'));
        }

        return $this->redirectToRoute('app_moderation_users');
    }
}
