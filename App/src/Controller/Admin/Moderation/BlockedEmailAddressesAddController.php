<?php

namespace App\Controller\Admin\Moderation;

use App\Entity\BlockedEmailAddress;
use App\Entity\User;
use App\Repository\BlockedEmailAddressRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_MODERATOR")'), statusCode: 403, exceptionCode: 10010)]
class BlockedEmailAddressesAddController extends AbstractController
{
    protected EntityManagerInterface $entityManager;
    protected BlockedEmailAddressRepository $blockedEmailAddressRepository;
    protected UserRepository $userRepository;

    #[Route(path: '/admin/moderation/blocked-email-addresses/block', name: 'app_moderation_blocked_email_addresses_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->blockedEmailAddressRepository = $entityManager->getRepository(BlockedEmailAddress::class);
        $this->userRepository = $entityManager->getRepository(User::class);

        $action = 'add';
        $blockedEmailAddress = new BlockedEmailAddress();

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('admin/moderation/add_blocked_email_address.html.twig');
            }

            $blockedEmailAddress->setEmailAddress($request->get('email'));
            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/moderation/add_blocked_email_address.html.twig', [
                    'errors' => $fieldErrors,
                    'blocked_email_address' => $blockedEmailAddress,
                ]);
            }

            $entityManager->persist($blockedEmailAddress);
            $entityManager->flush();

            $this->addFlash('notice', 'Email address blocked');

            return $this->redirectToRoute('app_moderation_blocked_email_addresses');
        }

        return $this->render('admin/moderation/add_blocked_email_address.html.twig', [
            'blocked_email_address' => $blockedEmailAddress,
        ]);
    }

    protected function validateRequest(Request $request)
    {
        $errors = [];

        if (empty($request->get('email')) || !filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'This is not a valid email address';
        } elseif ($this->blockedEmailAddressRepository->findOneBy(['email_address' => $request->get('email')])) {
            $errors['email'][] = 'This email address is already blocked';
        } elseif ($this->userRepository->findOneBy(['email_address' => $request->get('email')])) {
            $errors['email'][] = 'This email address belongs to an existing user, you must ban the user instead';
        }

        return $errors;
    }
}
