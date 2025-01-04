<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserSettingsController extends AbstractController
{
    private UserRepository $userRepository;

    #[Route(path: '/settings', name: 'app_settings')]
    public function settings(
        #[CurrentUser] ?User $user,
    ): Response
    {
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/settings.html.twig', [
            'user' => $user,
        ]);
    }
}
