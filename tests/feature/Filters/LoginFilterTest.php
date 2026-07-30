<?php
namespace Tests\Feature\Filters;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class LoginFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
        $this->logout();
    }

    protected function tearDown(): void
    {
        $this->truncateAll();
        parent::tearDown();
    }

    public function testUnauthenticatedRedirectsToLogin()
    {
        $result = $this->get('/overview');
        $result->assertStatus(302);
    }

    public function testUnauthenticatedCanAccessLogin()
    {
        $result = $this->get('/login');
        $result->assertStatus(200);
    }

    public function testUnauthenticatedCannotAccessAdmin()
    {
        $result = $this->get('/overview');
        $result->assertStatus(302);
    }

    public function testUnauthenticatedCannotAccessJabatan()
    {
        $result = $this->get('/admin/jabatan');
        $result->assertStatus(302);
    }

    public function testUnauthenticatedCannotAccessProfile()
    {
        $result = $this->get('/profile');
        $result->assertStatus(302);
    }
}
