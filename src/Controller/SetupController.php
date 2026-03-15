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
use Symfony\Contracts\Translation\TranslatorInterface;

class SetupController extends AbstractController
{
    /**
     * The setup routes are explicitly bypassed in the RedirectToSetupRequestListener. If adding a new setup step, it is
     * important to also add to the listener to prevent a redirect loop.
     */
    private const SETUP_STEP_TO_ROUTE = [
        'admin' => 'app_setup_admin',
        'location' => 'app_setup_location',
        'categories' => 'app_setup_categories',
        'image_storage' => 'app_setup_image_storage',
        'mail' => 'app_setup_mail',
        'complete' => 'app_index_index',
    ];

    public const LANGUAGES = [
        'en' => 'English',
        'it' => 'Italiano',
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
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/setup', name: 'app_setup_language')]
    public function language(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (!empty($setupSetting)) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/language.html.twig', [
                'languages' => self::LANGUAGES,
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('setup/language.html.twig', [
                'languages' => self::LANGUAGES,
            ]);
        }

        $fieldErrors = $this->validateSetupLanguageRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/language.html.twig', [
                'errors' => $fieldErrors,
                'languages' => self::LANGUAGES,
                'values' => [
                    'language' => $request->request->get('language'),
                ],
            ]);
        }

        $languageSetting = $settingsRepository->getSettingByName('language');
        if (!$languageSetting) {
            $languageSetting = new Settings();
            $languageSetting->setName('language');
        }
        $languageSetting->setValue($request->request->get('language'));

        $setupSetting = new Settings();
        $setupSetting
            ->setName('setup')
            ->setValue('admin')
        ;

        $this->entityManager->persist($setupSetting);
        $this->entityManager->persist($languageSetting);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_setup_admin');
    }

    #[Route(path: '/setup/admin', name: 'app_setup_admin')]
    public function admin(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_language');
        }

        if ('admin' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/admin.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('setup/admin.html.twig');
        }

        $fieldErrors = $this->validateSetupAdminUserRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/admin.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'username' => $request->request->get('username'),
                    'email' => $request->request->get('email'),
                ],
            ]);
        }

        $admin = new User();
        $admin
            ->setUsername($request->request->get('username'))
            ->setEmailAddress($request->request->get('email'))
            ->setCreated(new \DateTimeImmutable())
            ->setEmailVerified(true)
            ->setRoles(['ROLE_SUPER_ADMIN'])
        ;

        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            $request->request->get('password')
        );

        $admin->setPassword($hashedPassword);

        $entityErrors = $this->validator->validate($admin);
        if (count($entityErrors) > 0) {
            $this->addFlash(
                'warning',
                $this->translator->trans('setup.create_admin_account.validations.generic'),
            );

            return $this->render('setup/admin.html.twig');
        }

        $setupSetting->setValue('location');
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
            return $this->redirectToRoute('app_setup_language');
        }

        if ('location' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/location.html.twig');
        }

        $submittedToken = $request->getPayload()->get('token');

        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('setup/location.html.twig');
        }

        $fieldErrors = $this->validateSetupLocationRequest($request);

        if (!empty($fieldErrors)) {
            return $this->render('setup/location.html.twig', [
                'errors' => $fieldErrors,
                'values' => [
                    'locationLatLng' => $request->request->get('locationLatLng'),
                    'locationMeasurement' => $request->request->get('locationMeasurement'),
                    'locationRadiusMeters' => $request->request->get('locationRadiusMeters'),
                    'locationZoom' => $request->request->get('locationZoom'),
                ],
            ]);
        }

        $locationRadiusSetting = $settingsRepository->getSettingByName('locationRadiusMeters');
        if (!$locationRadiusSetting) {
            $locationRadiusSetting = new Settings();
            $locationRadiusSetting->setName('locationRadiusMeters');
        }
        $locationRadiusSetting->setValue($request->request->get('locationRadiusMeters'));
        $this->entityManager->persist($locationRadiusSetting);

        $locationMeasurementSetting = $settingsRepository->getSettingByName('locationMeasurement');
        if (!$locationMeasurementSetting) {
            $locationMeasurementSetting = new Settings();
            $locationMeasurementSetting->setName('locationMeasurement');
        }
        $locationMeasurementSetting->setValue($request->request->get('locationMeasurement'));
        $this->entityManager->persist($locationMeasurementSetting);

        $locationZoomSetting = $settingsRepository->getSettingByName('locationZoom');
        if (!$locationZoomSetting) {
            $locationZoomSetting = new Settings();
            $locationZoomSetting->setName('locationZoom');
        }
        $locationZoomSetting->setValue($request->request->get('locationZoom'));
        $this->entityManager->persist($locationZoomSetting);

        $locationLatLngSetting = $settingsRepository->getSettingByName('locationLatLng');
        if (!$locationLatLngSetting) {
            $locationLatLngSetting = new Settings();
            $locationLatLngSetting->setName('locationLatLng');
        }
        $locationLatLngSetting->setValue($request->request->get('locationLatLng'));
        $this->entityManager->persist($locationLatLngSetting);

        $timezoneSetting = $settingsRepository->getSettingByName('timezone');
        if (!$timezoneSetting) {
            $timezoneSetting = new Settings();
            $timezoneSetting->setName('timezone');
        }
        $timezone = LatLong::getTimezone($request->request->get('locationLatLng'));
        $timezoneSetting->setValue($timezone->getName());
        $this->entityManager->persist($timezoneSetting);

        $setupSetting->setValue('categories');

        $this->entityManager->persist($setupSetting);
        $this->entityManager->flush();

        return $this->render('setup/categories.html.twig', [
            'suggested_categories' => $this->getSuggestedCategories(),
        ]);
    }

    #[Route(path: '/setup/categories', name: 'app_setup_categories')]
    public function categories(Request $request): Response
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $setupSetting = $settingsRepository->getSettingByName('setup');

        if (empty($setupSetting)) {
            return $this->redirectToRoute('app_setup_language');
        }

        if ('categories' !== $setupSetting->getValue()) {
            return $this->redirectToRoute(self::SETUP_STEP_TO_ROUTE[$setupSetting->getValue()]);
        }

        $suggestedCategories = $this->getSuggestedCategories();
        if ('POST' !== $request->getMethod()) {
            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => $suggestedCategories,
            ]);
        }

        $selectedCategories = [];
        foreach ($request->request->all() as $requestParamKey => $requestParamValue) {
            if (array_key_exists($requestParamKey, $suggestedCategories)) {
                $selectedCategories[$requestParamKey] = $suggestedCategories[$requestParamKey];
            }
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('setup', $submittedToken)) {
            $this->addFlash(
                'warning',
                $this->translator->trans('fields.csrf_token.validations.invalid'),
            );

            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => $suggestedCategories,
                'selected_categories' => $selectedCategories,
            ]);
        }

        if (empty($selectedCategories)) {
            return $this->render('setup/categories.html.twig', [
                'suggested_categories' => $suggestedCategories,
                'errors' => [
                    $this->translator->trans('setup.add_categories.validations.empty'),
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
            return $this->redirectToRoute('app_setup_language');
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
                $this->translator->trans('fields.csrf_token.validations.invalid'),
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
                    'storageOption' => $request->request->get('storageOption'),
                    'region' => $request->request->get('region'),
                    'bucketName' => $request->request->get('bucketName'),
                    'accessKey' => $request->request->get('accessKey'),
                    'secretKey' => $request->request->get('secretKey'),
                ],
            ]);
        }

        if ('s3' === $request->request->get('storageOption')) {
            $s3RegionSetting = $settingsRepository->getSettingByName('s3Region');
            if (!$s3RegionSetting) {
                $s3RegionSetting = new Settings();
                $s3RegionSetting->setName('s3Region');
            }
            $s3RegionSetting->setValue($request->request->get('region'));
            $this->entityManager->persist($s3RegionSetting);

            $s3BucketNameSetting = $settingsRepository->getSettingByName('s3BucketName');
            if (!$s3BucketNameSetting) {
                $s3BucketNameSetting = new Settings();
                $s3BucketNameSetting->setName('s3BucketName');
            }
            $s3BucketNameSetting->setValue($request->request->get('bucketName'));
            $this->entityManager->persist($s3BucketNameSetting);

            $s3AccessKeySetting = $settingsRepository->getSettingByName('s3AccessKey');
            if (!$s3AccessKeySetting) {
                $s3AccessKeySetting = new Settings();
                $s3AccessKeySetting->setName('s3AccessKey');
            }
            $s3AccessKeySetting->setValue($request->request->get('accessKey'));
            $this->entityManager->persist($s3AccessKeySetting);

            $s3SecretKeySetting = $settingsRepository->getSettingByName('s3SecretKey');
            if (!$s3SecretKeySetting) {
                $s3SecretKeySetting = new Settings();
                $s3SecretKeySetting->setName('s3SecretKey');
            }
            $s3SecretKeySetting->setEncryptedValue($request->request->get('secretKey'));
            $this->entityManager->persist($s3SecretKeySetting);
        }

        $imageStorageSetting = new Settings();
        $imageStorageSetting
            ->setName('imageStorage')
            ->setValue($request->request->get('storageOption'))
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
            return $this->redirectToRoute('app_setup_language');
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
                $this->translator->trans('fields.csrf_token.validations.invalid'),
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
                    'smtpUsername' => $request->request->get('smtpUsername'),
                    'smtpPassword' => $request->request->get('smtpPassword'),
                    'smtpServer' => $request->request->get('smtpServer'),
                    'smtpPort' => $request->request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->request->get('smtpFromEmailAddress'),
                    'smtpToEmailAddress' => $request->request->get('smtpToEmailAddress'),
                ],
            ]);
        }

        if (!empty($request->request->get('smtpToEmailAddress'))) {
            $mailException = null;
            try {
                $this->mailer->sendTest(
                    $request->request->get('smtpUsername'),
                    $request->request->get('smtpPassword'),
                    $request->request->get('smtpServer'),
                    (int) $request->request->get('smtpPort'),
                    $request->request->get('smtpToEmailAddress'),
                    $request->request->get('smtpFromEmailAddress'),
                );
            } catch (TransportExceptionInterface $e) {
                $mailException = $e->getMessage();
            }

            return $this->render('setup/mail.html.twig', [
                'email_sent_to' => $request->request->get('smtpToEmailAddress'),
                'email_sent_exception' => $mailException,
                'encryption_key_exists' => $encryptionKeyExists,
                'values' => [
                    'smtpUsername' => $request->request->get('smtpUsername'),
                    'smtpPassword' => $request->request->get('smtpPassword'),
                    'smtpServer' => $request->request->get('smtpServer'),
                    'smtpPort' => $request->request->get('smtpPort'),
                    'smtpFromEmailAddress' => $request->request->get('smtpFromEmailAddress'),
                    'smtpToEmailAddress' => $request->request->get('smtpToEmailAddress'),
                ],
            ]);
        }

        $smtpUsername = $settingsRepository->getSettingByName('smtpUsername');
        if (!$smtpUsername) {
            $smtpUsername = new Settings();
            $smtpUsername->setName('smtpUsername');
        }
        $smtpUsername->setValue($request->request->get('smtpUsername'));
        $this->entityManager->persist($smtpUsername);

        $smtpPassword = $settingsRepository->getSettingByName('smtpPassword');
        if (!$smtpPassword) {
            $smtpPassword = new Settings();
            $smtpPassword->setName('smtpPassword');
        }
        $smtpPassword->setEncryptedValue($request->request->get('smtpPassword'));
        $this->entityManager->persist($smtpPassword);

        $smtpServer = $settingsRepository->getSettingByName('smtpServer');
        if (!$smtpServer) {
            $smtpServer = new Settings();
            $smtpServer->setName('smtpServer');
        }
        $smtpServer->setValue($request->request->get('smtpServer'));
        $this->entityManager->persist($smtpServer);

        $smtpPort = $settingsRepository->getSettingByName('smtpPort');
        if (!$smtpPort) {
            $smtpPort = new Settings();
            $smtpPort->setName('smtpPort');
        }
        $smtpPort->setValue($request->request->get('smtpPort'));
        $this->entityManager->persist($smtpPort);

        $smtpFromEmailAddress = $settingsRepository->getSettingByName('smtpFromEmailAddress');
        if (!$smtpFromEmailAddress) {
            $smtpFromEmailAddress = new Settings();
            $smtpFromEmailAddress->setName('smtpFromEmailAddress');
        }
        $smtpFromEmailAddress->setValue($request->request->get('smtpFromEmailAddress'));
        $this->entityManager->persist($smtpFromEmailAddress);

        $setupSetting->setValue('complete');
        $this->entityManager->persist($setupSetting);

        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_index');
    }

    private function validateSetupMailRequest(Request $request): array
    {
        $errors = [];

        if (empty($_ENV['ENCRYPTION_KEY'])) {
            $errors['smtpPassword'][] = $this->translator->trans('fields.smtp_password.validations.no_encryption_key');
        }

        if (empty($request->request->get('smtpServer'))) {
            $errors['smtpServer'][] = $this->translator->trans('fields.smtp_server.validations.empty');
        }

        if (empty($request->request->get('smtpPort')) || !is_numeric($request->request->get('smtpPort'))) {
            $errors['smtpPort'][] = $this->translator->trans('fields.smtp_port.validations.invalid');
        }

        if (empty($request->request->get('smtpFromEmailAddress')) || !filter_var($request->request->get('smtpFromEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpFromEmailAddress'][] = $this->translator->trans('fields.sender_email_address.validations.invalid');
        }

        if (!empty($request->request->get('smtpToEmailAddress')) && !filter_var($request->request->get('smtpToEmailAddress'), FILTER_VALIDATE_EMAIL)) {
            $errors['smtpToEmailAddress'][] = $this->translator->trans('fields.recipient_email_address.validations.invalid');
        }

        return $errors;
    }

    private function validateSetupImageStorageRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->request->get('storageOption')) || !in_array($request->request->get('storageOption'), self::IMAGE_STORAGE_OPTIONS)) {
            $errors['storageOption'][] = $this->translator->trans('setup.image_storage.validations.no_option_selected');
        }

        if ('s3' !== $request->request->get('storageOption')) {
            return $errors;
        }

        // All validation from here on is for the S3 storage option.

        if (empty($_ENV['ENCRYPTION_KEY'])) {
            $errors['secretKey'][] = $this->translator->trans('fields.amazon_s3_secret_key.validations.no_encryption_key');
        }

        if (empty($request->request->get('region')) || !in_array($request->request->get('region'), AmazonS3::REGIONS)) {
            $errors['region'][] = $this->translator->trans('fields.amazon_s3_region.validations.empty');
        }

        if (empty($request->request->get('bucketName'))) {
            $errors['bucketName'][] = $this->translator->trans('fields.amazon_s3_bucket_name.validations.empty');
        }

        if (empty($request->request->get('accessKey'))) {
            $errors['accessKey'][] = $this->translator->trans('fields.amazon_s3_access_key.validations.empty');
        }

        if (empty($request->request->get('secretKey'))) {
            $errors['secretKey'][] = $this->translator->trans('fields.amazon_s3_secret_key.validations.empty');
        }

        if (!empty($errors)) {
            return $errors;
        }

        // All validation from here on relates to connecting to the S3 API.

        $s3 = new AmazonS3();
        try {
            $s3->testSettings(
                $request->request->get('region'),
                $request->request->get('bucketName'),
                $request->request->get('accessKey'),
                $request->request->get('secretKey')
            );
        } catch (S3Exception $exception) {
            $errors['s3'] = [
                'summary' => $this->translator->trans('setup.image_storage.warnings.amazon_s3_exception'),
                'detail' => $exception->getMessage(),
            ];
        }

        return $errors;
    }

    private function validateSetupLanguageRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->request->get('language'))) {
            $errors['language'][] = $this->translator->trans(
                'fields.language.validations.empty',
            );
        }

        if (!array_key_exists($request->request->get('language'), self::LANGUAGES)) {
            $errors['language'][] = $this->translator->trans(
                'fields.language.validations.empty',
            );
        }

        return $errors;
    }

    private function validateSetupAdminUserRequest(Request $request): array
    {
        $errors = [];

        if (empty($request->request->get('username')) || mb_strlen($request->request->get('username')) < User::USERNAME_MIN_LENGTH) {
            $errors['username'][] = $this->translator->trans(
                'fields.username.validations.minimum_characters',
                [
                    '%character_length%' => User::USERNAME_MIN_LENGTH,
                ]
            );
        }

        if (mb_strlen($request->request->get('username')) > User::USERNAME_MAX_LENGTH) {
            $errors['username'][] = $this->translator->trans(
                'fields.username.validations.maximum_characters',
                [
                    '%character_length%' => User::USERNAME_MAX_LENGTH,
                ]
            );
        }

        if (!empty($request->request->get('username') && !ctype_alnum($request->request->get('username')))) {
            $errors['username'][] = $this->translator->trans('fields.username.validations.alphabetic_numeric');
        }

        if (empty($request->request->get('email')) || !filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = $this->translator->trans('fields.email_address.validations.invalid_email_address');
        }

        if (
            empty($request->request->get('password'))
            || mb_strlen($request->request->get('password') < User::PASSWORD_MIN_LENGTH)
            || !preg_match('/[A-Z]/', $request->request->get('password'))
            || !preg_match('/[a-z]/', $request->request->get('password'))
            || !preg_match('/[0-9]/', $request->request->get('password'))
        ) {
            $errors['password'][] = $this->translator->trans('fields.password.validations.weak_password');
        }

        return $errors;
    }

    private function validateSetupLocationRequest(Request $request): array
    {
        $errors = [];

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

        return $errors;
    }

    private function getSuggestedCategories(): array
    {
        return [
            'sightseeing' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.sightseeing.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.sightseeing.description'),
                'location' => CategoryLocationOptionsEnum::REQUIRED,
            ],
            'news_events' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.news_events.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.news_events.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
            ],
            'food_drink' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.food_drink.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.food_drink.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
                'allow_posting' => false,
            ],
            'history' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.history.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.history.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
            ],
            'businesses' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.businesses.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.businesses.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
            ],
            'sports_recreation' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.sports_recreation.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.sports_recreation.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
                'allow_posting' => false,
            ],
            'community_initiatives' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.community_initiatives.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.community_initiatives.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
            ],
            'habitat_meta' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.habitat_meta.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.habitat_meta.description'),
                'location' => CategoryLocationOptionsEnum::DISABLED,
            ],
            'random' => [
                'name' => $this->translator->trans('setup.add_categories.suggested_categories.random.name'),
                'description' => $this->translator->trans('setup.add_categories.suggested_categories.random.description'),
                'location' => CategoryLocationOptionsEnum::OPTIONAL,
            ],
        ];
    }
}
