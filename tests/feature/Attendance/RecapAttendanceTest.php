<?php
namespace Tests\Feature\Attendance;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class RecapAttendanceTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
        $this->seedPresensiData();
    }

    protected function tearDown(): void
    {
        $this->truncateAll();
        parent::tearDown();
    }

    public function testAttendanceSummaryReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/attendance-summary');
        $result->assertStatus(200);
    }
}
