<?php

namespace App\Controller;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route(path: '/', name: 'app_index_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $settingsRepository = $entityManager->getRepository(Settings::class);

        $habitatName = $settingsRepository->getSettingByName('name');
        if (is_null($habitatName)) {
            return $this->render('setup.html.twig');
        }
        return $this->render('index.html.twig', [
            'variable_test' => 'IndexController',
        ]);
    }
}
