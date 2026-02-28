<?php

namespace App\Utilities;

use Aws\S3\S3Client;

class AmazonS3
{
    private const TEST_FILE_NAME = 'habitat_test_object.txt';

    private const TEST_FILE_CONTENTS = 'This file is created by Habitat to test the ability to create, view and delete files in Amazon S3';

    public const REGIONS = [
        'af-south-1',
        'ap-east-1',
        'ap-northeast-1',
        'ap-northeast-2',
        'ap-northeast-3',
        'ap-south-1',
        'ap-southeast-1',
        'ap-southeast-2',
        'ap-southeast-3',
        'ca-central-1',
        'cn-north-1',
        'cn-northwest-1',
        'eu-central-1',
        'eu-north-1',
        'eu-south-1',
        'eu-west-1',
        'eu-west-2',
        'eu-west-3',
        'me-south-1',
        'sa-east-1',
        'us-east-1',
        'us-east-2',
        'us-gov-east-1',
        'us-gov-west-1',
        'us-west-1',
        'us-west-2',
    ];

    public function testSettings(string $region, string $bucketName, string $accessKey, string $secretKey)
    {
        $s3Client = new S3Client([
            'region' => $region,
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
        ]);

        $s3Client->putObject([
            'Bucket' => $bucketName,
            'Key' => self::TEST_FILE_NAME,
            'Body' => self::TEST_FILE_CONTENTS,
        ]);

        $s3Client->getObject([
            'Bucket' => $bucketName,
            'Key' => self::TEST_FILE_NAME,
        ]);

        $s3Client->deleteObject([
            'Bucket' => $bucketName,
            'Key' => self::TEST_FILE_NAME,
        ]);
    }
}
