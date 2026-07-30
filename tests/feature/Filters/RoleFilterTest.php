<?php
namespace Tests\Feature\Filters;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class RoleFilterTest extends CIUnitTestCase
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

    public function testHeadCanAccessOverview()
    {
        $this->loginAsHead();
        $result = $this->get('/overview');
        $result->assertStatus(200);
    }

    public function testAdminCanAccessOverview()
    {
        $this->loginAsAdmin();
        $result = $this->get('/overview');
        $result->assertStatus(200);
    }

    public function testEmployeeCannotAccessAdminPegawai()
    {
        $this->loginAsEmployee();
        $result = $this->get('/admin/pegawai');
        $result->assertStatus(302);
    }

    public function testEmployeeCanAccessOverview()
    {
        $this->loginAsEmployee();
        $result = $this->get('/overview');
        $result->assertStatus(200);
    }

    public function testHeadCanAccessJabatan()
    {
        $this->loginAsHead();
        $result = $this->get('/admin/jabatan');
        $result->assertStatus(200);
    }

    public function testEmployeeCannotAccessJabatan()
    {
        $this->loginAsEmployee();
        $result = $this->get('/admin/jabatan');
        $result->assertStatus(302);
    }

    public function testHeadCanAccessLokasi()
    {
        $this->loginAsHead();
        $result = $this->get('/admin/lokasi');
        $result->assertStatus(200);
    }

    public function testAdminCanAccessPegawai()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/pegawai');
        $result->assertStatus(200);
    }

    public function testEmployeeCanAccessAttendanceSummary()
    {
        $this->loginAsEmployee();
        $result = $this->get('/attendance-summary');
        $result->assertStatus(200);
    }
}
