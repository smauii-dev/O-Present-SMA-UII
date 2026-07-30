<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class S3 extends BaseConfig
{
    public string $endpoint;
    public string $publicEndpoint;
    public string $region;
    public string $accessKey;
    public string $secretKey;
    public string $bucket;
    public bool $usePathStyle;
    public int $presignedUrlExpiry;

    public function __construct()
    {
        parent::__construct();

        // Accept several env key aliases used across .env.dev / .env.production / docs.
        // Prefer explicit FORCE key, then generic endpoint, then dotted CI-style keys.
        $this->endpoint = (string) (
            env('S3_ENDPOINT_FORCE')
            ?: env('S3_ENDPOINT')
            ?: env('s3.endpoint')
            ?: 'http://smauii-rustfs:9000'
        );

        $this->publicEndpoint = (string) (
            env('S3_PUBLIC_ENDPOINT')
            ?: env('s3.public_endpoint')
            ?: $this->endpoint
        );

        $this->region = (string) (env('S3_REGION') ?: env('s3.region') ?: 'us-east-1');

        $this->accessKey = (string) (
            env('S3_ACCESS_KEY')
            ?: env('s3.key')
            ?: env('s3.access_key')
            ?: 'opresent-access'
        );

        $this->secretKey = (string) (
            env('S3_SECRET_KEY')
            ?: env('s3.secret')
            ?: env('s3.secret_key')
            ?: 'opresent-secret'
        );

        $this->bucket = (string) (env('S3_BUCKET') ?: env('s3.bucket') ?: 'opresent');

        $pathStyle = env('S3_USE_PATH_STYLE');
        if ($pathStyle === null || $pathStyle === false || $pathStyle === '') {
            $pathStyle = env('s3.use_path_style_endpoint', true);
        }
        $this->usePathStyle = filter_var($pathStyle, FILTER_VALIDATE_BOOLEAN);

        $this->presignedUrlExpiry = (int) (env('S3_PRESIGNED_URL_EXPIRY') ?: 3600);
    }
}
