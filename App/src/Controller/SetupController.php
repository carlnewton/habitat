<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryLocationOptionsEnum;
use App\Entity\Settings;
use App\Entity\User;
use App\Utilities\AmazonS3;
use App\Utilities\LatLong;
use App\Utilities\Mailer;
use Aws\S3\Exception\S3Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SetupController extends AbstractController
{
    /**
     * The setup routes are explicitly bypassed in the RedirectToSetupRequestListener. If adding a new setup step, it is
     * important to also add to the listener to prevent a redirect loop.
     */
    private const SETUP_STEP_TO_ROUTE = [
        'location' => 'app_setup_location',
        'categories' => 'app_setup_categories',
        'image_storage' => 'app_setup_image_storage',
        'mail' => 'app_setup_mail',
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

    private const IMAGE_STORAGE_OPTIONS = [
        'local',
        's3',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private Security $security,
        private Mailer $mailer,
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
        $this->entityManager->persist($setupSetting);
        $this->entityManager->persist($admin);
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

        $setupSetting->setValue('categories');

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

        $setupSetting->setValue('image_storage');
        $this->entityManager->persist($setupSetting);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_setup_image_storage');
    }

    #[Route(path: '/setup/image-storage', name: 'app_setup_image_storage')]
    public function imageStorage(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_admin');
        }

        if ('image_storage' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        $encryptionKeyExists = !empty($_ENV['ENCRYPTION_KEY']);

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/image_storage.html.twig', [
                'regions' => AmazonS3::REGIONS,
                'encryption_key_exists' => $encryptionKeyExists,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('setup/image_storage.html.twig', [
                'regions' => AmazonS3::REGIONS,
                'encryption_key_exists' => $encryptionKeyExists,
            ]);
        }

        $fieldErrors = $this->validateSetupImageStorageRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/image_storage.html.twig', [
                'errors' => $fieldErrors,
                'regions' => AmazonS3::REGIONS,
                'encryption_key_exists' => $encryptionKeyExists,
                'values' => [
                    'storageOption' => $request->get('storageOption'),
                    'region' => $request->get('region'),
                    'bucketName' => $request->get('bucketName'),
                    'accessKey' => $request->get('accessKey'),
                    'secretKey' => $request->get('secretKey'),
                ],
            ]);
        }

        if ('s3' === $request->get('storageOption')) {
            $s3RegionSetting = $settingsRepository->getSettingByName('s3Region');
            if (!$s3RegionSetting) {
                $s3RegionSetting = new Settings();
                $s3RegionSetting->setName('s3Region');
            }
            $s3RegionSetting->setValue($request->get('region'));
            $this->entityManager->persist($s3RegionSetting);

            $s3BucketNameSetting = $settingsRepository->getSettingByName('s3BucketName');
            if (!$s3BucketNameSetting) {
                $s3BucketNameSetting = new Settings();
                $s3BucketNameSetting->setName('s3BucketName');
            }
            $s3BucketNameSetting->setValue($request->get('bucketName'));
            $this->entityManager->persist($s3BucketNameSetting);

            $s3AccessKeySetting = $settingsRepository->getSettingByName('s3AccessKey');
            if (!$s3AccessKeySetting) {
                $s3AccessKeySetting = new Settings();
                $s3AccessKeySetting->setName('s3AccessKey');
            }
            $s3AccessKeySetting->setValue($request->get('accessKey'));
            $this->entityManager->persist($s3AccessKeySetting);

            $s3SecretKeySetting = $settingsRepository->getSettingByName('s3SecretKey');
            if (!$s3SecretKeySetting) {
                $s3SecretKeySetting = new Settings();
                $s3SecretKeySetting->setName('s3SecretKey');
            }
            $s3SecretKeySetting->setEncryptedValue($request->get('secretKey'));
            $this->entityManager->persist($s3SecretKeySetting);
        }

        $imageStorageSetting = new Settings();
        $imageStorageSetting
            ->setName('imageStorage')
            ->setValue($request->get('storageOption'))
        ;
        $this->entityManager->persist($imageStorageSetting);

        $setupSetting->setValue('mail');
        $this->entityManager->persist($setupSetting);

        $this->entityManager->flush();

        return $this->redirectToRoute('app_setup_mail');
    }

    #[Route(path: '/setup/mail', name: 'app_setup_mail')]
    public function mail(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_admin');
        }

        if ('mail' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        $encryptionKeyExists = !empty($_ENV['ENCRYPTION_KEY']);

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/mail.html.twig', [
                'encryption_key_exists' => $encryptionKeyExists,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                'Something went wrong, please try again.'
            );

            return $this->render('setup/mail.html.twig', [
                'encryption_key_exists' => $encryptionKeyExists,
            ]);
        }

        $fieldErrors = $this->validateSetupMailRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/mail.html.twig', [
                'errors' => $fieldErrors,
                'encryption_key_exists' => $encryptionKeyExists,
                'values' => [
                    'smtpUsername' => $request->get('smtpUsername'),
                    'smtpPassword' => $request->get('smtpPassword'),
                    'smtpServer' => $request->get('smtpServer'),
                    'smtpPort' => $request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->get('smtpFromEmailAddress'),
                    'smtpToEmailAddress' => $request->get('smtpToEmailAddress'),
                ],
            ]);
        }

        if (!empty($request->get('smtpToEmailAddress'))) {
            $mailException = null;
            try {
                $this->mailer->sendTest(
                    $request->get('smtpUsername'),
                    $request->get('smtpPassword'),
                    $request->get('smtpServer'),
                    (int) $request->get('smtpPort'),
                    $request->get('smtpToEmailAddress'),
                    $request->get('smtpFromEmailAddress'),
                );
            } catch (TransportExceptionInterface $e) {
                $mailException = $e->getMessage();
            }

            return $this->render('setup/mail.html.twig', [
                'email_sent_to' => $request->get('smtpToEmailAddress'),
                'email_sent_exception' => $mailException,
                'encryption_key_exists' => $encryptionKeyExists,
                'values' => [
                    'smtpUsername' => $request->get('smtpUsername'),
                    'smtpPassword' => $request->get('smtpPassword'),
                    'smtpServer' => $request->get('smtpServer'),
                    'smtpPort' => $request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->get('smtpFromEmailAddress'),
                    'smtpToEmailAddress' => $request->get('smtpToEmailAddress'),
                ],
            ]);
        }

        $smtpUsername = $settingsRepository->getSettingByName('smtpUsername');
        if (!$smtpUsername) {
            $smtpUsername = new Settings();
            $smtpUsername->setName('smtpUsername');
        }
        $smtpUsername->setValue($request->get('smtpUsername'));
        $this->entityManager->persist($smtpUsername);

        $smtpPassword = $settingsRepository->getSettingByName('smtpPassword');
        if (!$smtpPassword) {
            $smtpPassword = new Settings();
            $smtpPassword->setName('smtpPassword');
        }
        $smtpPassword->setEncryptedValue($request->get('smtpPassword'));
        $this->entityManager->persist($smtpPassword);

        $smtpServer = $settingsRepository->getSettingByName('smtpServer');
        if (!$smtpServer) {
            $smtpServer = new Settings();
            $smtpServer->setName('smtpServer');
        }
        $smtpServer->setValue($request->get('smtpServer'));
        $this->entityManager->persist($smtpServer);

        $smtpPort = $settingsRepository->getSettingByName('smtpPort');
        if (!$smtpPort) {
            $smtpPort = new Settings();
            $smtpPort->setName('smtpPort');
        }
        $smtpPort->setValue($request->get('smtpPort'));
        $this->entityManager->persist($smtpPort);

        $smtpFromEmailAddress = $settingsRepository->getSettingByName('smtpFromEmailAddress');
        if (!$smtpFromEmailAddress) {
            $smtpFromEmailAddress = new Settings();
            $smtpFromEmailAddress->setName('smtpFromEmailAddress');
        }
        $smtpFromEmailAddress->setValue($request->get('smtpFromEmailAddress'));
        $this->entityManager->persist($smtpFromEmailAddress);

        $setupSetting->setValue('complete');
        $this->entityManager->persist($setupSetting);

        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_index');
    }

    private function validateSetupMailRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->get('smtpUsername'))) {
            $errors['smtpUsername'][] = 'You must enter an SMTP username';
        }

        if (empty($request->get('smtpPassword'))) {
            $errors['smtpPassword'][] = 'You must enter an SMTP password';
        }

        if (empty($_ENV['ENCRYPTION_KEY'])) {
            $errors['smtpPassword'][] = 'The password key cannot be saved unless an ENCRYPTION_KEY environment variable is set';
        }

        if (empty($request->get('smtpServer'))) {
            $errors['smtpServer'][] = 'You must enter an SMTP server';
        }

        if (empty($request->get('smtpPort')) || !is_numeric($request->get('smtpPort'))) {
            $errors['smtpPort'][] = 'You must enter a valid port number';
        }

        if (empty($request->get('smtpFromEmailAddress')) || !filter_var($request->get('smtpFromEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpFromEmailAddress'][] = 'You must enter a valid sender email address';
        }

        if (!empty($request->get('smtpToEmailAddress')) && !filter_var($request->get('smtpToEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpToEmailAddress'][] = 'You must enter a valid recipient email address';
        }

        return $errors;
    }

    private function validateSetupImageStorageRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->get('storageOption')) || !in_array($request->get('storageOption'), self::IMAGE_STORAGE_OPTIONS)) {
            $errors['storageOption'][] = 'You must choose an image storage option';
        }

        if ('s3' !== $request->get('storageOption')) {
            return $errors;
        }

        // All validation from here on is for the S3 storage option.

        if (empty($_ENV['ENCRYPTION_KEY'])) {
            $errors['secretKey'][] = 'The secret key cannot be saved unless an ENCRYPTION_KEY environment variable is set';
        }

        if (empty($request->get('region')) || !in_array($request->get('region'), AmazonS3::REGIONS)) {
            $errors['region'][] = 'You must select the region of your S3 bucket';
        }

        if (empty($request->get('bucketName'))) {
            $errors['bucketName'][] = 'You must enter the name of your S3 bucket';
        }

        if (empty($request->get('accessKey'))) {
            $errors['accessKey'][] = 'You must enter the access key for your S3 bucket';
        }

        if (empty($request->get('secretKey'))) {
            $errors['secretKey'][] = 'You must enter the secret key for your S3 bucket';
        }

        if (!empty($errors)) {
            return $errors;
        }

        // All validation from here on relates to connecting to the S3 API.

        $s3 = new AmazonS3();
        try {
            $s3->testSettings(
                $request->get('region'),
                $request->get('bucketName'),
                $request->get('accessKey'),
                $request->get('secretKey')
            );
        } catch (S3Exception $exception) {
            $errors['s3'] = [
                'summary' => 'An error occurred when attempting to connect to the S3 bucket',
                'detail' => $exception->getMessage(),
            ];
        }

        return $errors;
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
