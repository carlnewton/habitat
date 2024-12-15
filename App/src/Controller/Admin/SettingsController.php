<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class SettingsController extends AbstractController
{
    #[Route(path: '/admin', name: 'app_admin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $settingsRepository = $entityManager->getRepository(Settings::class);

        if ('POST' === $request->getMethod()) {
            $submittedToken = $request->getPayload()->get('token');
            if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
                $this->addFlash(
                    'warning',
                    'Something went wrong, please try again.'
                );

                return $this->render('admin/index.html.twig');
            }

            $fieldErrors = $this->validateRequest($request);

            if (!empty($fieldErrors)) {
                return $this->render('admin/index.html.twig', [
                    'errors' => $fieldErrors,
                    'values' => [
                        'habitatName' => $request->get('habitatName'),
                        'domain' => $request->get('domain'),
                        'locationLatLng' => $request->get('locationLatLng'),
                        'locationMeasurement' => $request->get('locationMeasurement'),
                        'locationRadiusMeters' => $request->get('locationRadiusMeters'),
                        'locationZoom' => $request->get('locationZoom'),
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

            $domainSetting = $settingsRepository->getSettingByName('domain');
            if (!$domainSetting) {
                $domainSetting = new Settings();
                $domainSetting->setName('domain');
            }
            $domainSetting->setValue($request->get('domain'));

            $entityManager->persist($domainSetting);

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

            $entityManager->flush();

            $this->addFlash(
                'notice',
                'Settings saved'
            );

            return $this->redirectToRoute('app_admin_index');
        }

        $habitatNameSetting = $settingsRepository->getSettingByName('habitatName');
        $domainSetting = $settingsRepository->getSettingByName('domain');
        $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
        $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
        $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
        $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');

        return $this->render('admin/index.html.twig', [
            'values' => [
                'habitatName' => ($habitatNameSetting) ? $habitatNameSetting->getValue() : '',
                'domain' => ($domainSetting) ? $domainSetting->getValue() : '',
                'locationLatLng' => ($locationLatLngSetting) ? $locationLatLngSetting->getValue() : '51,0',
                'locationZoom' => ($locationZoomSetting) ? $locationZoomSetting->getValue() : '3',
                'locationMeasurement' => ($locationMeasurementSetting) ? $locationMeasurementSetting->getValue() : 'km',
                'locationRadiusMeters' => ($locationRadiusSetting) ? $locationRadiusSetting->getValue() : '3000',
            ],
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->get('habitatName')) > Settings::HABITAT_NAME_MAX_LENGTH) {
            $errors['habitatName'][] = 'Your Habitat name must be a maximum of ' . Settings::HABITAT_NAME_MAX_LENGTH . ' characters';
        }

        if (
            empty($request->get('locationLatLng'))
            || !LatLong::isValidLatLong($request->get('locationLatLng'))
        ) {
            $errors['location'][] = 'You must choose a valid location';
        }

        if (
            empty($request->get('domain'))
            || !filter_var($request->get('domain'), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
        ) {
            $errors['domain'][] = 'You must enter a valid domain';
        }

        if (
            empty($request->get('locationMeasurement'))
            || !in_array($request->get('locationMeasurement'), ['km', 'miles'])
            || $request->get('locationRadiusMeters') != (int) $request->get('locationRadiusMeters')
            || $request->get('locationRadiusMeters') < 1
        ) {
            $errors['location'][] = 'You must choose a valid location size';
        }

        return $errors;
    }
}
