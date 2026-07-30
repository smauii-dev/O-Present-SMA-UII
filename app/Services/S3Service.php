<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Common\Exception\RuntimeException as AwsException;
use Config\S3;

class S3Service
{
    protected S3Client $client;
    protected string $bucket;
    protected string $publicEndpoint;

    public function __construct()
    {
        $config = config('S3');

        $this->client = S3Client::factory([
            'version'     => 'latest',
            'region'      => $config->region,
            'endpoint'    => $config->endpoint,
            'use_path_style_endpoint' => $config->usePathStyle,
            'credentials' => [
                'key'    => $config->accessKey,
                'secret' => $config->secretKey,
            ],
        ]);

        $this->bucket = $config->bucket;
        $this->publicEndpoint = $config->publicEndpoint;
    }

    public function getClient(): S3Client
    {
        return $this->client;
    }

    /**
     * Upload raw binary data to S3.
     *
     * @param string $key      Object key (e.g. "profile/user-123.jpg")
     * @param string $body     Raw file contents
     * @param string $mime     MIME type
     * @return array{key: string, url: string}
     */
    public function upload(string $key, string $body, string $mime = 'image/jpeg'): array
    {
        $this->ensureBucket();

        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $body,
            'ContentType' => $mime,
            'ACL'         => 'public-read',
        ]);

        return [
            'key' => $key,
            'url' => $this->getObjectUrl($key),
        ];
    }

    /**
     * Delete an object from S3.
     */
    public function delete(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            log_message('error', 'S3 delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a presigned URL for PUT (direct browser upload).
     *
     * @param string $key      Object key
     * @param int    $expires  Seconds until the URL expires
     * @return array{upload_url: string, key: string, expires_in: int}
     */
    public function getPresignedPutUrl(string $key, int $expires = 3600): array
    {
        $cmd = $this->client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'ACL'    => 'public-read',
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expires} seconds");

        return [
            'upload_url' => (string) $request->getUri(),
            'key'        => $key,
            'expires_in' => $expires,
        ];
    }

    /**
     * Generate a presigned URL for GET (temporary read access).
     */
    public function getPresignedGetUrl(string $key, int $expires = 3600): string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expires} seconds");

        return (string) $request->getUri();
    }

    /**
     * Get the public URL of an object.
     * Uses S3_PUBLIC_ENDPOINT if configured, otherwise falls back to internal endpoint.
     */
    public function getObjectUrl(string $key): string
    {
        // If public endpoint is configured, use it for browser-accessible URLs
        if ($this->publicEndpoint) {
            // Path-style URL: https://public-endpoint/bucket/key
            return rtrim($this->publicEndpoint, '/') . '/' . $this->bucket . '/' . $key;
        }

        // Fallback to internal endpoint (only works from within Docker network)
        return $this->client->getObjectUrl($this->bucket, $key);
    }

    /**
     * Ensure the bucket exists, create if not.
     */
    public function ensureBucket(): void
    {
        if (!$this->client->doesBucketExist($this->bucket)) {
            $this->client->createBucket([
                'Bucket' => $this->bucket,
            ]);

            // Automatically apply a public-read policy so files can be accessed via URL
            $policy = '{
                "Version": "2012-10-17",
                "Statement": [
                    {
                        "Sid": "PublicReadGetObject",
                        "Effect": "Allow",
                        "Principal": "*",
                        "Action": "s3:GetObject",
                        "Resource": "arn:aws:s3:::' . $this->bucket . '/*"
                    }
                ]
            }';

            try {
                $this->client->putBucketPolicy([
                    'Bucket' => $this->bucket,
                    'Policy' => $policy,
                ]);
            } catch (AwsException $e) {
                log_message('error', 'Failed to set public bucket policy: ' . $e->getMessage());
            }
        }
    }
}