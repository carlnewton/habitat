<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use App\Entity\Settings;
use App\Entity\User;
use App\Utilities\LatLong;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SetupController extends AbstractController
{
    private const SETUP_STEP_TO_ROUTE = [
        'location' => 'app_setup_location',
        'categories' => 'app_setup_categories',
        'complete' => 'app_index_index',
    ];

    private const SUGGESTED_CATEGORIES = [
        'sightseeing' => [
            'name' => 'Sightseeing',
            'description' => 'A space for sharing and discussing visual discoveries, landmarks, nature spots, street art, hidden gems, and other unique finds in the area.',
            'location' => CategoryLocationOptionsEnum::REQUIRED,
        ],
        'news_events' => [
            'name' => 'News and Events',
            'description' => 'Posts related to news updates, events, festivals, concerts, or community gatherings.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'food_drink' => [
            'name' => 'Food and Drink',
            'description' => 'Discussions and pictures of restaurants, cafes, food trucks, or special dishes.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
            'allow_posting' => false,
        ],
        'history' => [
            'name' => 'History',
            'description' => 'Pictures and discussions specifically focused on the historical significance, stories, and events related to local historical sites, buildings, or events in the area.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'businesses' => [
            'name' => 'Businesses',
            'description' => 'Posts promoting or discussing shops, boutiques, or services.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'sports_recreation' => [
            'name' => 'Sports and Recreation',
            'description' => 'Conversations about outdoor activities, or recreational facilities.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
            'allow_posting' => false,
        ],
        'community_initiatives' => [
            'name' => 'Community Initiatives',
            'description' => 'Posts about charities, volunteer opportunities, or community projects.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'habitat_meta' => [
            'name' => 'Habitat Meta',
            'description' => 'Discussions about this instance of Habitat.',
            'location' => CategoryLocationOptionsEnum::DISABLED,
        ],
        'random' => [
            'name' => 'Random',
            'description' => 'A catch-all for various topics that do not fit anywhere else.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private Security $security
    ) {
    }

    #[Route(path: '/setup', name: 'app_setup_admin')]
    public function admin(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (!empty($setupSetting)) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        $userRepository = $this->entityManager->getRepository(User::class);

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/admin.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('setup/admin.html.twig');
        }

        $fieldErrors = $this->validateSetupAdminUserRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/admin.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'username' => $request->get('username'),
                    'email' => $request->get('email'),
                ],
            ]);
        }

        $admin = new User();
        $admin
            ->setUsername($request->get('username'))
            ->setEmailAddress($request->get('email'))
            ->setCreated(new \DateTimeImmutable())
            ->setEmailVerified(true)
            ->setRoles(['ROLE_SUPER_ADMIN'])
        ;

        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $request->get('password')
        );

        $admin->setPassword($hashedPassword);

        $entityErrors = $this->validator->validate($admin);
        if (count($entityErrors) > 0) {
            $this->addFlash(
                'warning',
                'Something went wrong with your details, please try again.'
            );

            return $this->render('setup/admin.html.twig');
        }
        $setupSetting = new Settings();
        $setupSetting
            ->setName('setup')
            ->setValue('location')
        ;

        $this->entityManager->persist($admin);
        $this->entityManager->persist($setupSetting);
        $this->entityManager->flush();

        $this->security->login($admin, 'form_login');

        return $this->redirectToRoute('app_setup_location');
    }

    #[Route(path: '/setup/location', name: 'app_setup_location')]
    public function location(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_admin');
        }

        if ('location' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        $userRepository = $this->entityManager->getRepository(User::class);

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/location.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('setup/location.html.twig');
        }

        $fieldErrors = $this->validateSetupLocationRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/location.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'locationLatLng' => $request->get('locationLatLng'),
                    'locationMeasurement' => $request->get('locationMeasurement'),
                    'locationRadiusMeters' => $request->get('locationRadiusMeters'),
                    'locationZoom' => $request->get('locationZoom'),
                ],
            ]);
        }

        $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');
        if (!$locationRadiusSetting) {
            $locationRadiusSetting = new Settings();
            $locationRadiusSetting->setName('locationRadiusMeters');
        }
        $locationRadiusSetting->setValue($request->get('locationRadiusMeters'));
        $this->entityManager->persist($locationRadiusSetting);

        $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
        if (!$locationMeasurementSetting) {
            $locationMeasurementSetting = new Settings();
            $locationMeasurementSetting->setName('locationMeasurement');
        }
        $locationMeasurementSetting->setValue($request->get('locationMeasurement'));
        $this->entityManager->persist($locationMeasurementSetting);

        $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
        if (!$locationZoomSetting) {
            $locationZoomSetting = new Settings();
            $locationZoomSetting->setName('locationZoom');
        }
        $locationZoomSetting->setValue($request->get('locationZoom'));
        $this->entityManager->persist($locationZoomSetting);

        $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
        if (!$locationLatLngSetting) {
            $locationLatLngSetting = new Settings();
            $locationLatLngSetting->setName('locationLatLng');
        }
        $locationLatLngSetting->setValue($request->get('locationLatLng'));
        $this->entityManager->persist($locationLatLngSetting);

        $setupSetting = $settingsRepository->getSettingByName('setup');
        $setupSetting
            ->setName('setup')
            ->setValue('categories')
        ;

        $this->entityManager->persist($setupSetting);
        $this->entityManager->flush();

        return $this->render('setup/categories.html.twig', [
            'suggested_categories' => self::SUGGESTED_CATEGORIES,
        ]);
    }

    #[Route(path: '/setup/categories', name: 'app_setup_categories')]
    public function categories(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_admin');
        }

        if ('categories' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => self::SUGGESTED_CATEGORIES,
            ]);
        }

        $selectedCategories = [];
        foreach ($request->request->all() as $requestParamKey => $requestParamValue) {
            if (array_key_exists($requestParamKey, self::SUGGESTED_CATEGORIES)) {
                $selectedCategories[$requestParamKey] = self::SUGGESTED_CATEGORIES[$requestParamKey];
            }
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => self::SUGGESTED_CATEGORIES,
                'selected_categories' => $selectedCategories,
            ]);
        }

        if (empty($selectedCategories)) {
            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => self::SUGGESTED_CATEGORIES,
                'errors' => [
                    'You must select at least one category. Don\'t worry, you can change these later.',
                ],
            ]);
        }

        foreach ($selectedCategories as $categoryReference => $categoryProperties) {
            $category = new Category();
            $category
                ->setName($categoryProperties['name'])
                ->setDescription($categoryProperties['description'])
                ->setLocation($categoryProperties['location'])
                ->setAllowPosting(true)
            ;

            $this->entityManager->persist($category);
        }

        $setupSetting = $settingsRepository->getSettingByName('setup');
        $setupSetting
            ->setName('setup')
            ->setValue('complete')
        ;

        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_index');
    }

    private function validateSetupAdminUserRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->get('username')) || mb_strlen($request->get('username')) < User::USERNAME_MIN_LENGTH) {
            $errors['username'][] = 'Your username must be a minimum of ' . User::USERNAME_MIN_LENGTH . ' characters';
        }

        if (mb_strlen($request->get('username')) > User::USERNAME_MAX_LENGTH) {
            $errors['username'][] = 'Your username must be a maximum of ' . User::USERNAME_MAX_LENGTH . ' characters';
        }

        if (!empty($request->get('username') && !ctype_alnum($request->get('username')))) {
            $errors['username'][] = 'Your username must only use alphabetic and numeric characters';
        }

        if (empty($request->get('email')) || !filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'This is not a valid email address';
        }

        if (
            empty($request->get('password'))
            || mb_strlen($request->get('password') < User::PASSWORD_MIN_LENGTH)
            || !preg_match('/[A-Z]/', $request->get('password'))
            || !preg_match('/[a-z]/', $request->get('password'))
            || !preg_match('/[0-9]/', $request->get('password'))
        ) {
            $errors['password'][] = 'You must use a stronger password';
        }

        return $errors;
    }

    private function validateSetupLocationRequest(Request $request): array
    {
        $errors = [];

        if (
            empty($request->get('locationLatLng'))
            || !LatLong::isValidLatLong($request->get('locationLatLng'))
        ) {
            $errors['location'][] = 'You must choose a valid location';
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
