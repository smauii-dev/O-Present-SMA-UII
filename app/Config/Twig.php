<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Twig extends BaseConfig
{
    public string|bool $autoescape = 'html';
    public array $paths = [APPPATH . 'Views'];
    public string $fileExtension = 'twig';
    public bool $debug = (ENVIRONMENT === 'development');
    public bool $cache = (ENVIRONMENT === 'production');
    public string $cacheDir = WRITEPATH . 'twig';
}
