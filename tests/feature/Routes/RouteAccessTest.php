<?php
namespace Tests\Feature\Routes;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class RouteAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

    private array $publicRoutes = [
        '/login',
    ];

    private array $adminRoutes = [
        '/overview',
        '/admin/jabatan',
        '/admin/lokasi',
        '/admin/pegawai',
        '/attendance-summary',
        '/absence',
        '/profile',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        $this->truncateAll();
        parent::tearDown();
    }

    public function testPublicRoutesAreAccessible()
    {
        foreach ($this->publicRoutes as $route) {
            $result = $this->get($route);
            $this->assertContains($result->response()->getStatusCode(), [200, 302], "Route {$route} should be accessible");
        }
    }

    public function testAdminRoutesExist()
    {
        $this->loginAsAdmin();
        foreach ($this->adminRoutes as $route) {
            $result = $this->get($route);
            $this->assertContains($result->response()->getStatusCode(), [200, 302], "Route {$route} should exist and respond");
        }
    }
}
