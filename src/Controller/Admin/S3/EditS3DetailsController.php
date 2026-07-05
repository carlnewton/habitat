<?php

namespace App\Controller\Admin\S3;

use App\Entity\Settings;
use App\Utilities\AmazonS3;
use Aws\S3\Exception\S3Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN', statusCode: 403, exceptionCode: 10010)]
class EditS3DetailsController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/admin/s3', name: 'app_admin_s3_details', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $settingsRepository = $entityManager->getRepository(Settings::class);
        $imageStorageSetting = $settingsRepository->getSettingByName('imageStorage');
        if (empty($imageStorageSetting) || 's3' !== $imageStorageSetting->getValue()) {
            // Once the Habitat has been set up, it would be a very involved task to cleanly switch between local and s3
            // storage, because existing files would have to be moved. Disk space, scheduled task management etc would
            // all have to be considered. For now, we make the settings page unavailable to instances that have already
            // been set to use local storage.
            throw $this->createNotFoundException('This page is unavailable.');
        }

        $savedRegion = $settingsRepository->getSettingByName('s3Region');
        $savedBucketName = $settingsRepository->getSettingByName('s3BucketName');
        $savedAccessKey = $settingsRepository->getSettingByName('s3AccessKey');

        if ('POST' !== $request->getMethod()) {
            return $this->render('admin/s3/details.html.twig', [
                'regions' => AmazonS3::REGIONS,
                'values' => [
                    'region' => $savedRegion->getValue(),
                    'bucketName' => $savedBucketName->getValue(),
                    'accessKey' => $savedAccessKey->getValue(),
                ],
            ]);
        }

        $submittedToken = $request->getPayload()->get('token');
        if (!$this->isCsrfTokenValid('admin', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('fields.csrf_token.validations.invalid'));

            return $this->render('admin/s3/details.html.twig', [
                'regions' => AmazonS3::REGIONS,
                'values' => [
                    'region' => $savedRegion->getValue(),
                    'bucketName' => $savedBucketName->getValue(),
                    'accessKey' => $savedAccessKey->getValue(),
                ],
            ]);
        }

        $savedSecretKey = $settingsRepository->getSettingByName('s3SecretKey');
        $fieldErrors = $this->validateRequest($request, $savedSecretKey);

        if (!empty($fieldErrors)) {
            return $this->render('admin/s3/details.html.twig', [
                'regions' => AmazonS3::REGIONS,
                'errors' => $fieldErrors,
                'values' => [
                    'region' => $request->request->get('region'),
                    'bucketName' => $request->request->get('bucketName'),
                    'accessKey' => $request->request->get('accessKey'),
                    'secretKey' => $request->request->get('secretKey'),
                ],
            ]);
        }

        $savedRegion->setValue($request->request->get('region'));
        $entityManager->persist($savedRegion);

        $savedBucketName->setValue($request->request->get('bucketName'));
        $entityManager->persist($savedBucketName);

        $savedAccessKey->setValue($request->request->get('accessKey'));
        $entityManager->persist($savedAccessKey);

        if (!empty($request->request->get('secretKey'))) {
            $savedSecretKey->setEncryptedValue($request->request->get('secretKey'));
            $entityManager->persist($savedSecretKey);
        }

        $entityManager->flush();

        $this->addFlash('notice', 'Amazon S3 details updated');

        return $this->redirectToRoute('app_admin_s3_details');
    }

    private function validateRequest(Request $request, Settings $savedSecretKey): array
    {
        $errors = [];

        if (empty($request->request->get('region')) || !in_array($request->request->get('region'), AmazonS3::REGIONS)) {
            $errors['region'][] = $this->translator->trans('fields.amazon_s3_region.validations.empty');
        }

        if (empty($request->request->get('bucketName'))) {
            $errors['bucketName'][] = $this->translator->trans('fields.amazon_s3_bucket_name.validations.empty');
        }

        if (empty($request->request->get('accessKey'))) {
            $errors['accessKey'][] = $this->translator->trans('fields.amazon_s3_access_key.validations.empty');
        }

        if (!empty($errors)) {
            return $errors;
        }

        if (empty($request->request->get('secretKey'))) {
            $secretKey = $savedSecretKey->getEncryptedValue();
        } else {
            $secretKey = $request->request->get('secretKey');
        }

        $s3 = new AmazonS3();
        try {
            $s3->testSettings(
                $request->request->get('region'),
                $request->request->get('bucketName'),
                $request->request->get('accessKey'),
                $secretKey
            );
        } catch (S3Exception $exception) {
            $errors['s3'] = [
                'summary' => $this->translator->trans('setup.image_storage.warnings.amazon_s3_exception'),
                'detail' => $exception->getMessage(),
            ];
        }

        return $errors;
    }
}
