<?php

namespace App\Controller\HTMX;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SetMeasurementUnitsController extends AbstractController
{
    #[Route(path: '/hx/set-measurement-units', name: 'app_hx_set_measurement_units', methods: ['POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        if (null === $user) {
            return $this->render('partials/hx/must_sign_in.html.twig');
        }

        $measurement = null;

        if (!is_null($request->get('km'))) {
            $measurement = 'km';
        }

        if (!is_null($request->get('miles'))) {
            $measurement = 'miles';
        }

        if (is_null($measurement)) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('set_measurement_units', $submittedToken)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $userSettingsRepository = $entityManager->getRepository(UserSettings::class);
        $userSetting = $userSettingsRepository->findOneBy([
            'name' => 'locationMeasurement',
            'user' => $user->getId(),
        ]);

        if (!$userSetting) {
            $userSetting = new UserSettings();
            $userSetting->setName('locationMeasurement');
            $userSetting->setUser($user);
        }

        $userSetting->setValue($measurement);
        $entityManager->persist($userSetting);
        $entityManager->flush();

        return $this->render('partials/hx/location_measurement.html.twig', [
            'locationMeasurement' => $measurement,
        ]);
    }
}
