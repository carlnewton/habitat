<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SettingsRepository;
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
        SettingsRepository $settings,
    ): Response {
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        $userMeasurementUnitSetting = null;
        if (!empty($user->getSettings())) {
            foreach ($user->getSettings() as $userSetting) {
                if ('locationMeasurement' === $userSetting->getName()) {
                    $userMeasurementUnitSetting = $userSetting;
                    break;
                }
            }
        }

        $locationMeasurement = null;
        if (is_null($userMeasurementUnitSetting)) {
            $measurementUnitSetting = $settings->getSettingByName('locationMeasurement');
            $locationMeasurement = $measurementUnitSetting->getValue();
        } else {
            $locationMeasurement = $userMeasurementUnitSetting->getValue();
        }

        return $this->render('security/settings.html.twig', [
            'user' => $user,
            'locationMeasurement' => $locationMeasurement,
        ]);
    }
}
