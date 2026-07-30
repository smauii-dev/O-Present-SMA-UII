<?php
namespace Tests\Feature\Employee;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class EmployeeCrudTest extends CIUnitTestCase
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

    public function testIndexReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/pegawai');
        $result->assertStatus(200);
    }

    public function testFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/pegawai/form');
        $result->assertStatus(200);
    }

    public function testFragmentReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/pegawai/fragment');
        $result->assertStatus(200);
    }

    public function testImportFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/pegawai/import-form');
        $result->assertStatus(200);
    }
}
