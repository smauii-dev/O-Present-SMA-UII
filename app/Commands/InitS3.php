<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\S3Service;

class InitS3 extends BaseCommand
{
    protected $group       = 'S3';
    protected $name        = 's3:init';
    protected $description = 'Initialize S3 bucket and upload default assets';
    protected $usage       = 's3:init';
    protected $options     = [];
    protected $examples    = [
        's3:init' => 'Create bucket and upload default profile photo',
    ];

    public function run(array $params)
    {
        CLI::write('Initializing S3 bucket...', 'cyan');

        $s3 = new S3Service();

        // Ensure bucket exists
        try {
            $s3->ensureBucket();
            CLI::write('  Bucket ensured', 'green');
        } catch (\Exception $e) {
            CLI::error('  Failed to create bucket: ' . $e->getMessage());
            return;
        }

        // Upload default profile photo
        $defaultPhoto = FCPATH . 'assets/img/user_profile/default.jpg';
        if (file_exists($defaultPhoto)) {
            $content = file_get_contents($defaultPhoto);
            try {
                $s3->upload('profile/default.jpg', $content, 'image/jpeg');
                CLI::write('  Uploaded profile/default.jpg', 'green');
            } catch (\Exception $e) {
                CLI::error('  Failed to upload default.jpg: ' . $e->getMessage());
            }
        } else {
            CLI::write('  default.jpg not found at ' . $defaultPhoto, 'yellow');
        }

        CLI::write('S3 initialization complete!', 'cyan');
    }
}
