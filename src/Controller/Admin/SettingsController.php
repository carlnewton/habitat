<?php

namespace App\Controller\Admin;

use App\Controller\SetupController;
use App\Entity\Settings;
use App\Entity\SidebarContent;
use App\Entity\User;
use App\Entity\UserSettings;
use App\Repository\SettingsRepository;
use App\Repository\SidebarContentRepository;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class SettingsController extends AbstractController
{
    private SettingsRepository $settingsRepository;
    private SidebarContentRepository $sidebarContentRepository;

    public function __construct(
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager,
    ) {
        $this->settingsRepository = $this->entityManager->getRepository(Settings::class);
        $this->sidebarContentRepository = $this->entityManager->getRepository(SidebarContent::class);
    }

    #[Route(path: '/admin/settings', name: 'app_admin_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[CurrentUser] ?User $user,
    ): Response {
        $sidebarContent = $this->sidebarContentRepository->findOneBy([]);
        if (!$sidebarContent) {
            $sidebarContent = new SidebarContent();
        }

        $userSettingsRepository = $this->entityManager->getRepository(UserSettings::class);
        $adminUserThemeSetting = $userSettingsRepository->findOneBy([
            'name' => 'theme',
            'user' => $user->getId(),
        ]);

        $adminUserTheme = null;
        if ($adminUserThemeSetting) {
            $adminUserTheme = $this->translator->trans('fields.theme.' . $adminUserThemeSetting->getValue());
        }

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    $this->translator->trans('fields.csrf_token.validations.invalid'),
                );

                return $this->render('admin/settings.html.twig', [
                    'languages' => SetupController::LANGUAGES,
                    'adminUserTheme' => $adminUserTheme,
                    'sidebarContent' => ($sidebarContent->getContent()) ? $sidebarContent->getContent() : '',
                ]);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/settings.html.twig', [
                    'languages' => SetupController::LANGUAGES,
                    'adminUserTheme' => $adminUserTheme,
                    'errors' => $fieldErrors,
                    'values' => [
                        'habitatName' => $request->request->get('habitatName'),
                        'locationLatLng' => $request->request->get('locationLatLng'),
                        'locationMeasurement' => $request->request->get('locationMeasurement'),
                        'locationRadiusMeters' => $request->request->get('locationRadiusMeters'),
                        'locationZoom' => $request->request->get('locationZoom'),
                        'language' => $request->request->get('language'),
                        'theme' => $request->request->get('theme'),
                        'sidebarContent' => $request->request->get('sidebarContent'),
                    ],
                ]);
            }

            $this->persistSetting('habitatName', $request->request->get('habitatName'));
            $this->persistSetting('locationRadiusMeters', $request->request->get('locationRadiusMeters'));
            $this->persistSetting('locationMeasurement', $request->request->get('locationMeasurement'));
            $this->persistSetting('locationZoom', $request->request->get('locationZoom'));
            $this->persistSetting('language', $request->request->get('language'));
            $this->persistSetting('locationLatLng', $request->request->get('locationLatLng'));
            $timezone = LatLong::getTimezone($request->request->get('locationLatLng'));
            $this->persistSetting('timezone', $timezone->getName());
            $this->persistSetting('theme', $request->request->get('theme'));

            $sidebarContent->setContent($request->request->get('sidebarContent'));
            $this->entityManager->persist($sidebarContent);
            $this->entityManager->flush();

            $this->addFlash(
                'notice',
                'Settings saved'
            );

            return $this->redirectToRoute('app_admin_settings');
        }

        $habitatNameSetting = $this->settingsRepository->getSettingByName('habitatName');
        $locationLatLngSetting = $this->settingsRepository->getSettingByName('locationLatLng');
        $locationZoomSetting = $this->settingsRepository->getSettingByName('locationZoom');
        $languageSetting = $this->settingsRepository->getSettingByName('language');
        $themeSetting = $this->settingsRepository->getSettingByName('theme');
        $locationMeasurementSetting = $this->settingsRepository->getSettingByName('locationMeasurement');
        $locationRadiusSetting = $this->settingsRepository->getSettingByName('locationRadiusMeters');

        return $this->render('admin/settings.html.twig', [
            'languages' => SetupController::LANGUAGES,
            'adminUserTheme' => $adminUserTheme,
            'values' => [
                'habitatName' => ($habitatNameSetting) ? $habitatNameSetting->getValue() : '',
                'locationLatLng' => ($locationLatLngSetting) ? $locationLatLngSetting->getValue() : '51,0',
                'locationZoom' => ($locationZoomSetting) ? $locationZoomSetting->getValue() : '3',
                'language' => ($languageSetting) ? $languageSetting->getValue() : 'en',
                'theme' => ($themeSetting) ? $themeSetting->getValue() : 'light',
                'locationMeasurement' => ($locationMeasurementSetting) ? $locationMeasurementSetting->getValue() : 'km',
                'locationRadiusMeters' => ($locationRadiusSetting) ? $locationRadiusSetting->getValue() : '3000',
                'sidebarContent' => ($sidebarContent->getContent()) ? $sidebarContent->getContent() : '',
            ],
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->request->get('habitatName')) > Settings::HABITAT_NAME_MAX_LENGTH) {
            $errors['habitatName'][] = $this->translator->trans(
                'admin.settings.validations.habitat_name.max_characters',
                [
                    '%max_characters%' => Settings::HABITAT_NAME_MAX_LENGTH,
                ]
            );
        }

        if (
            empty($request->request->get('locationLatLng'))
            || !LatLong::isValidLatLong($request->request->get('locationLatLng'))
        ) {
            $errors['location'][] = $this->translator->trans('map.validations.invalid_location');
        }

        if (
            empty($request->request->get('locationMeasurement'))
            || !in_array($request->request->get('locationMeasurement'), ['km', 'miles'])
            || $request->request->get('locationRadiusMeters') != (int) $request->request->get('locationRadiusMeters')
            || $request->request->get('locationRadiusMeters') < 1
        ) {
            $errors['location'][] = $this->translator->trans('map.validations.invalid_location_size');
        }

        if (
            empty($request->request->get('language'))
            || !array_key_exists($request->request->get('language'), SetupController::LANGUAGES)
        ) {
            $errors['language'][] = $this->translator->trans('fields.language.validations.empty');
        }

        if (
            empty($request->request->get('theme'))
            || !in_array($request->request->get('theme'), ['light', 'dark'])
        ) {
            $errors['theme'][] = $this->translator->trans('fields.theme.validations.empty');
        }

        return $errors;
    }

    private function persistSetting(string $settingName, string $settingValue): void
    {
        $setting = $this->settingsRepository->getSettingByName($settingName);
        if (!$setting) {
            $setting = new Settings();
            $setting->setName($settingName);
        }
        $setting->setValue($settingValue);

        $this->entityManager->persist($setting);
    }
}
