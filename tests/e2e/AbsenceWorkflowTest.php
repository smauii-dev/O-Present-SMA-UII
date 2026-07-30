<?php
namespace Tests\E2E;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class AbsenceWorkflowTest extends CIUnitTestCase
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

    public function testEmployeeAbsenceWorkflow()
    {
        $this->loginAsEmployee();

        $result = $this->get('/absence');
        $result->assertStatus(200);

        $result = $this->get('/absence/form');
        $result->assertStatus(200);

        $result = $this->get('/admin/absence/admin');
        $result->assertStatus(302);
    }

    public function testHeadAbsenceManagement()
    {
        $this->loginAsHead();

        $result = $this->get('/admin/absence/admin');
        $result->assertStatus(200);

        $result = $this->get('/absence/form');
        $result->assertStatus(200);
    }
}
