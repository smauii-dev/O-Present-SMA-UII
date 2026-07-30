<?php
namespace Tests\Feature\AbsenceRequest;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class AbsenceRequestCrudTest extends CIUnitTestCase
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
        $this->loginAsEmployee();
        $result = $this->get('/absence');
        $result->assertStatus(200);
    }

    public function testFormReturns200()
    {
        $this->loginAsEmployee();
        $result = $this->get('/absence/form');
        $result->assertStatus(200);
    }

    public function testAdminAbsenceReturns200()
    {
        $this->loginAsHead();
        $result = $this->get('/admin/absence/admin');
        $result->assertStatus(200);
    }

    public function testEmployeeCannotAccessAdminAbsence()
    {
        $this->loginAsEmployee();
        $result = $this->get('/admin/absence/admin');
        $result->assertStatus(302);
    }
}
