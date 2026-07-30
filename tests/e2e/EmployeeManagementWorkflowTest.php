<?php
namespace Tests\E2E;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class EmployeeManagementWorkflowTest extends CIUnitTestCase
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

    public function testAdminFullManagementWorkflow()
    {
        $this->loginAsAdmin();

        $result = $this->get('/admin/pegawai');
        $result->assertStatus(200);

        $result = $this->get('/admin/pegawai/form');
        $result->assertStatus(200);

        $result = $this->get('/admin/jabatan');
        $result->assertStatus(200);

        $result = $this->get('/admin/lokasi');
        $result->assertStatus(200);
    }

    public function testEmployeeCannotAccessManagement()
    {
        $this->loginAsEmployee();

        $result = $this->get('/admin/pegawai');
        $result->assertStatus(302);

        $result = $this->get('/admin/jabatan');
        $result->assertStatus(302);

        $result = $this->get('/admin/lokasi');
        $result->assertStatus(302);
    }

    public function testRoleSwitchingWorkflow()
    {
        $this->loginAsHead();
        $result = $this->get('/overview');
        $result->assertStatus(200);
        $result = $this->get('/admin/jabatan');
        $result->assertStatus(200);

        $this->post('/logout');
        $this->loginAsAdmin();
        $result = $this->get('/overview');
        $result->assertStatus(200);
        $result = $this->get('/admin/pegawai');
        $result->assertStatus(200);
    }
}
