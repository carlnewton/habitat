<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NearbyController extends AbstractController
{
    #[Route(path: '/nearby', name: 'app_nearby_nearby', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager
    ): Response {
        $userRepository = $entityManager->getRepository(User::class);

        if (0 === $userRepository->count()) {
            return $this->redirectToRoute('app_setup_admin');
        }

        return $this->render('nearby.html.twig');
    }
}
