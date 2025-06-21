<?php

namespace App\Controller;

use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NearbyController extends AbstractController
{
    #[Route(path: '/nearby', name: 'app_nearby_nearby', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ('GET' === $request->getMethod()) {
            return $this->render('nearby.html.twig');
        }

        if (!LatLong::isValidLatLong($request->get('latLng', ''))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        return $this->render('partials/hx/nearby.html.twig', [
            'htmx' => true,
            'latLng' => $request->get('latLng'),
        ]);
    }
}
