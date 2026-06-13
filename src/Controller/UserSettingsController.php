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
    private ?User $user;

    public function __construct(
        private SettingsRepository $settings,
    ) {
    }

    #[Route(path: '/settings', name: 'app_settings')]
    public function settings(
        #[CurrentUser] ?User $user,
    ): Response {
        $this->user = $user;

        if (null === $this->user) {
            return $this->redirectToRoute('app_login');
        }

        $themeSetting = $this->getUserOrGlobalSetting('theme');
        $locationMeasurementSetting = $this->getUserOrGlobalSetting('locationMeasurement');

        return $this->render('security/settings.html.twig', [
            'user' => $this->user,
            'locationMeasurement' => ($locationMeasurementSetting) ? $locationMeasurementSetting->getValue() : 'kilometers',
            'theme' => ($themeSetting) ? $themeSetting->getValue() : 'light',
        ]);
    }

    private function getUserOrGlobalSetting(string $settingName): mixed
    {
        $savedUserSetting = null;
        if (!empty($this->user->getSettings())) {
            foreach ($this->user->getSettings() as $userSetting) {
                if ($settingName === $userSetting->getName()) {
                    return $userSetting;
                }
            }
        }

        $savedGlobalSetting = $this->settings->getSettingByName($settingName);

        if (!empty($savedGlobalSetting)) {
            return $savedGlobalSetting;
        }

        return null;
    }
}
