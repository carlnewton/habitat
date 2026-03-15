<?php

namespace App\Controller\Admin;

use App\Controller\SetupController;
use App\Entity\Settings;
use App\Entity\SidebarContent;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class SettingsController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/settings', name: 'app_admin_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $settingsRepository = $entityManager->getRepository(Settings::class);
        $sidebarContentRepository = $entityManager->getRepository(SidebarContent::class);
        $sidebarContent = $sidebarContentRepository->findOneBy(['id' => 1]);
        if (!$sidebarContent) {
            $sidebarContent = new SidebarContent();
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
                    'sidebarContent' => ($sidebarContent->getContent()) ? $sidebarContent->getContent() : '',
                ]);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/settings.html.twig', [
                    'languages' => SetupController::LANGUAGES,
                    'errors' => $fieldErrors,
                    'values' => [
                        'habitatName' => $request->request->get('habitatName'),
                        'locationLatLng' => $request->request->get('locationLatLng'),
                        'locationMeasurement' => $request->request->get('locationMeasurement'),
                        'locationRadiusMeters' => $request->request->get('locationRadiusMeters'),
                        'locationZoom' => $request->request->get('locationZoom'),
                        'language' => $request->request->get('language'),
                        'sidebarContent' => $request->request->get('sidebarContent'),
                    ],
                ]);
            }

            $habitatNameSetting = $settingsRepository->getSettingByName('habitatName');
            if (!$habitatNameSetting) {
                $habitatNameSetting = new Settings();
                $habitatNameSetting->setName('habitatName');
            }
            $habitatNameSetting->setValue($request->request->get('habitatName'));

            $entityManager->persist($habitatNameSetting);

            $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');
            if (!$locationRadiusSetting) {
                $locationRadiusSetting = new Settings();
                $locationRadiusSetting->setName('locationRadiusMeters');
            }
            $locationRadiusSetting->setValue($request->request->get('locationRadiusMeters'));
            $entityManager->persist($locationRadiusSetting);

            $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
            if (!$locationMeasurementSetting) {
                $locationMeasurementSetting = new Settings();
                $locationMeasurementSetting->setName('locationMeasurement');
            }
            $locationMeasurementSetting->setValue($request->request->get('locationMeasurement'));
            $entityManager->persist($locationMeasurementSetting);

            $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
            if (!$locationZoomSetting) {
                $locationZoomSetting = new Settings();
                $locationZoomSetting->setName('locationZoom');
            }
            $locationZoomSetting->setValue($request->request->get('locationZoom'));
            $entityManager->persist($locationZoomSetting);

            $languageSetting = $settingsRepository->getSettingByName('language');
            if (!$languageSetting) {
                $languageSetting = new Settings();
                $languageSetting->setName('language');
            }
            $languageSetting->setValue($request->request->get('language'));
            $entityManager->persist($languageSetting);

            $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
            if (!$locationLatLngSetting) {
                $locationLatLngSetting = new Settings();
                $locationLatLngSetting->setName('locationLatLng');
            }
            $locationLatLngSetting->setValue($request->request->get('locationLatLng'));
            $entityManager->persist($locationLatLngSetting);

            $timezoneSetting = $settingsRepository->getSettingByName('timezone');
            if (!$timezoneSetting) {
                $timezoneSetting = new Settings();
                $timezoneSetting->setName('timezone');
            }
            $timezone = LatLong::getTimezone($request->request->get('locationLatLng'));
            $timezoneSetting->setValue($timezone->getName());
            $entityManager->persist($timezoneSetting);

            $sidebarContent->setContent($request->request->get('sidebarContent'));
            $entityManager->persist($sidebarContent);

            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Settings saved'
            );

            return $this->redirectToRoute('app_admin_settings');
        }

        $habitatNameSetting = $settingsRepository->getSettingByName('habitatName');
        $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
        $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
        $languageSetting = $settingsRepository->getSettingByName('language');
        $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
        $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');

        return $this->render('admin/settings.html.twig', [
            'languages' => SetupController::LANGUAGES,
            'values' => [
                'habitatName' => ($habitatNameSetting) ? $habitatNameSetting->getValue() : '',
                'locationLatLng' => ($locationLatLngSetting) ? $locationLatLngSetting->getValue() : '51,0',
                'locationZoom' => ($locationZoomSetting) ? $locationZoomSetting->getValue() : '3',
                'language' => ($languageSetting) ? $languageSetting->getValue() : 'en',
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

        if (SidebarContent::stripTags($request->request->get('sidebarContent')) !== $request->request->get('sidebarContent')) {
            $errors['sidebarContent'][] = $this->translator->trans('admin.settings.validations.sidebar_content.disallowed_html_tags');
        }

        return $errors;
    }
}
