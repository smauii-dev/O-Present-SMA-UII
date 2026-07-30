<?php

namespace Config;

use CodeIgniter\Cache\Handlers\DummyHandler;

class Cache extends \Config\Cache
{
    public string $handler = 'dummy';
    public array $validHandlers = [
        'dummy' => DummyHandler::class,
    ];
}