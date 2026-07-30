<?php

require_once __DIR__ . '/../../vendor/codeigniter4/framework/system/Test/bootstrap.php';

use CodeIgniter\Test\Mock\MockCache;

// The framework services are now loaded, we can override the cache service
\Config\Services::injectMock('cache', new MockCache());