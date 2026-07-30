<?php
namespace Tests\Feature\Position;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class PositionCrudTest extends CIUnitTestCase
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
        $result = $this->get('/admin/jabatan');
        $result->assertStatus(200);
    }

    public function testFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/jabatan/form');
        $result->assertStatus(200);
    }

    public function testEditFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/jabatan/form/1');
        $result->assertStatus(200);
    }

    public function testFragmentReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/jabatan/fragment');
        $result->assertStatus(200);
    }

    public function testDeleteJabatan()
    {
        $this->loginAsAdmin();
        $result = $this->delete('/admin/jabatan/3');
        $this->assertContains($result->response()->getStatusCode(), [200, 302, 409]);
    }
}
