<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NearbyController extends AbstractController
{
    #[Route(path: '/nearby', name: 'app_nearby_nearby', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
    ): Response {
        return $this->render('nearby.html.twig');
    }
}
