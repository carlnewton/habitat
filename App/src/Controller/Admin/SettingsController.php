<?php

namespace App\Controller\Admin;

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

    #[Route(path: '/admin', name: 'app_admin_index', methods: ['GET', 'POST'])]
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

                return $this->render('admin/index.html.twig', [
                    'sidebarContent' => ($sidebarContent->getContent()) ? $sidebarContent->getContent() : '',
                ]);
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/index.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'habitatName' => $request->get('habitatName'),
                        'locationLatLng' => $request->get('locationLatLng'),
                        'locationMeasurement' => $request->get('locationMeasurement'),
                        'locationRadiusMeters' => $request->get('locationRadiusMeters'),
                        'locationZoom' => $request->get('locationZoom'),
                        'sidebarContent' => $request->get('sidebarContent'),
                    ],
                ]);
            }

            $habitatNameSetting = $settingsRepository->getSettingByName('habitatName');
            if (!$habitatNameSetting) {
                $habitatNameSetting = new Settings();
                $habitatNameSetting->setName('habitatName');
            }
            $habitatNameSetting->setValue($request->get('habitatName'));

            $entityManager->persist($habitatNameSetting);

            $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');
            if (!$locationRadiusSetting) {
                $locationRadiusSetting = new Settings();
                $locationRadiusSetting->setName('locationRadiusMeters');
            }
            $locationRadiusSetting->setValue($request->get('locationRadiusMeters'));
            $entityManager->persist($locationRadiusSetting);

            $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
            if (!$locationMeasurementSetting) {
                $locationMeasurementSetting = new Settings();
                $locationMeasurementSetting->setName('locationMeasurement');
            }
            $locationMeasurementSetting->setValue($request->get('locationMeasurement'));
            $entityManager->persist($locationMeasurementSetting);

            $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
            if (!$locationZoomSetting) {
                $locationZoomSetting = new Settings();
                $locationZoomSetting->setName('locationZoom');
            }
            $locationZoomSetting->setValue($request->get('locationZoom'));
            $entityManager->persist($locationZoomSetting);

            $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
            if (!$locationLatLngSetting) {
                $locationLatLngSetting = new Settings();
                $locationLatLngSetting->setName('locationLatLng');
            }
            $locationLatLngSetting->setValue($request->get('locationLatLng'));
            $entityManager->persist($locationLatLngSetting);

            $sidebarContent->setContent($request->get('sidebarContent'));
            $entityManager->persist($sidebarContent);

            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Settings saved'
            );

            return $this->redirectToRoute('app_admin_index');
        }

        $habitatNameSetting = $settingsRepository->getSettingByName('habitatName');
        $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
        $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
        $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
        $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');

        return $this->render('admin/index.html.twig', [
            'values' => [
                'habitatName' => ($habitatNameSetting) ? $habitatNameSetting->getValue() : '',
                'locationLatLng' => ($locationLatLngSetting) ? $locationLatLngSetting->getValue() : '51,0',
                'locationZoom' => ($locationZoomSetting) ? $locationZoomSetting->getValue() : '3',
                'locationMeasurement' => ($locationMeasurementSetting) ? $locationMeasurementSetting->getValue() : 'km',
                'locationRadiusMeters' => ($locationRadiusSetting) ? $locationRadiusSetting->getValue() : '3000',
                'sidebarContent' => ($sidebarContent->getContent()) ? $sidebarContent->getContent() : '',
            ],
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->get('habitatName')) > Settings::HABITAT_NAME_MAX_LENGTH) {
            $errors['habitatName'][] = $this->translator->trans(
                'admin.settings.validations.habitat_name.max_characters',
                [
                    '%max_characters%' => Settings::HABITAT_NAME_MAX_LENGTH
                ]
            );
        }

        if (
            empty($request->get('locationLatLng'))
            || !LatLong::isValidLatLong($request->get('locationLatLng'))
        ) {
            $errors['location'][] = $this->translator->trans('map.validations.invalid_location');
        }

        if (
            empty($request->get('locationMeasurement'))
            || !in_array($request->get('locationMeasurement'), ['km', 'miles'])
            || $request->get('locationRadiusMeters') != (int) $request->get('locationRadiusMeters')
            || $request->get('locationRadiusMeters') < 1
        ) {
            $errors['location'][] = $this->translator->trans('map.validations.invalid_location_size');
        }

        if (SidebarContent::stripTags($request->get('sidebarContent')) !== $request->get('sidebarContent')) {
            $errors['sidebarContent'][] = $this->translator->trans('admin.settings.validations.sidebar_content.disallowed_html_tags');
        }

        return $errors;
    }
}
