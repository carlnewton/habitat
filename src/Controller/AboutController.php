<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/about', name: 'app_about', methods: ['GET'])]
    public function index(): Response
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $settingsRepository = $this->entityManager->getRepository(Settings::class);

        $admins = $userRepository->findUsersByRole('ROLE_SUPER_ADMIN');
        $admin = $admins[0];
        $domain = preg_replace('(^https?://)', '', getenv('SERVER_NAME'));

        $locationLatLng = $settingsRepository->getSettingByName('locationLatLng');
        $locationZoom = $settingsRepository->getSettingByName('locationZoom');
        $locationRadiusMeters = $settingsRepository->getSettingByName('locationRadiusMeters');

        return $this->render('about.html.twig', [
            'admin' => $admin,
            'domain' => $domain,
            'locationLatLng' => $locationLatLng?->getValue(),
            'locationZoom' => $locationZoom?->getValue() ?? 14,
            'locationRadiusMeters' => $locationRadiusMeters?->getValue() ?? 3000,
        ]);
    }
}
