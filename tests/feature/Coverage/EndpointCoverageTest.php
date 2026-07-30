<?php
namespace Tests\Feature\Coverage;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class EndpointCoverageTest extends CIUnitTestCase
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

    public function testHealthEndpointReturnsOk(): void
    {
        $result = $this->get('/health');
        $result->assertStatus(200);
        $result->assertSee('OK');
    }

    public function testAttendanceSummaryExportReturns200(): void
    {
        $this->loginAsEmployee();
        $result = $this->post('/attendance-summary/export', [
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
        ]);
        $result->assertStatus(200);
    }
}
