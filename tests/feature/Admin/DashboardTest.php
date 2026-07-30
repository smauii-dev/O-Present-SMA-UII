<?php

namespace Tests\Feature\Admin;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\AuthTrait;
use Tests\Support\Traits\DatabaseTrait;

final class DashboardTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

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

    public function testSplashRootReturns200()
    {
        $result = $this->get('/');
        $result->assertStatus(200);
    }

    public function testAdminOverviewReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/overview');
        $result->assertStatus(200);
    }

    public function testHeadOverviewReturns200()
    {
        $this->loginAsHead();
        $result = $this->get('/overview');
        $result->assertStatus(200);
    }
}
