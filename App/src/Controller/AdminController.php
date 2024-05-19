<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Settings;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class AdminController extends AbstractController
{
    #[Route(path: '/admin', name: 'app_admin_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $userRepository = $entityManager->getRepository(User::class);

        if ($userRepository->count() === 0) {
            return $this->redirectToRoute('app_setup_admin');
        }

        $settingsRepository = $entityManager->getRepository(Settings::class);

        if ($request->getMethod() === 'POST') {
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
                        'locationLatLng' => $request->get('locationLatLng'),
                        'locationMeasurement' => $request->get('locationMeasurement'),
                        'locationRadiusMeters' => $request->get('locationRadiusMeters'),
                        'locationZoom' => $request->get('locationZoom'),
                    ]
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
                'locationMeasurement' => ($locationMeasurementSetting) ? $locationMeasurementSetting->getValue() : 'kms',
                'locationRadiusMeters' => ($locationRadiusSetting) ? $locationRadiusSetting->getValue() : '3000',
            ]
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $errors = [];

        if (mb_strlen($request->get('habitatName')) > Settings::HABITAT_NAME_MAX_LENGTH) {
            $errors['habitatName'][] = 'Your Habitat name must be a maximum of ' . Settings::HABITAT_NAME_MAX_LENGTH . ' characters';
        }

        if (
            empty($request->get('locationLatLng')) ||
            !LatLong::isValidLatLong($request->get('locationLatLng'))
        ) {
            $errors['location'][] = 'You must choose a valid location';
        }

        if (
            empty($request->get('locationMeasurement')) ||
            !in_array($request->get('locationMeasurement'), ['kms', 'miles']) ||
            $request->get('locationRadiusMeters') != (int) $request->get('locationRadiusMeters') ||
            $request->get('locationRadiusMeters') < 1
        ) {
            $errors['location'][] = 'You must choose a valid location size';
        }

        return $errors;
    }
}
