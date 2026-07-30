<?php
namespace Tests\E2E;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class AttendanceWorkflowTest extends CIUnitTestCase
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

    public function testFullAttendanceWorkflow()
    {
        $this->loginAsEmployee();

        $result = $this->get('/overview');
        $result->assertStatus(200);

        $result = $this->get('/attendance-summary');
        $result->assertStatus(200);

        $result = $this->get('/overview');
        $result->assertStatus(200);

        $result = $this->post('/logout');
        $result->assertStatus(302);

        $result = $this->get('/overview');
        $result->assertStatus(302);
    }

    public function testAdminCanViewSummary()
    {
        $this->loginAsAdmin();

        $result = $this->get('/overview');
        $result->assertStatus(200);

        $result = $this->get('/attendance-summary');
        $result->assertStatus(200);
    }
}
