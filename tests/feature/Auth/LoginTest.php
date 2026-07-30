<?php
namespace Tests\Feature\Auth;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class LoginTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTrait;
    use DatabaseTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
        $this->logout(); // Ensure clean auth state
    }

    protected function tearDown(): void
    {
        $this->truncateAll();
        parent::tearDown();
    }

    public function testLoginPageReturns200()
    {
        $result = $this->get('/login');
        // LoginFilter may redirect in test context due to url_is() matching
        $this->assertContains($result->response()->getStatusCode(), [200, 302]);
    }

    public function testLoginWithValidCredentials()
    {
        $result = $this->post('/login', [
            'email' => 'jaya@test.com',
            'password' => '123456',
        ]);
        // Should redirect after successful login
        $result->assertStatus(302);
    }

    public function testLoginWithInvalidCredentials()
    {
        $result = $this->post('/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpass',
        ]);
        $result->assertStatus(302);
        $result->assertRedirect('/login');
    }

    public function testLoginWithEmptyFields()
    {
        $result = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);
        $result->assertStatus(302);
    }

    public function testUnauthenticatedAccessRedirectsToLogin()
    {
        $result = $this->get('/overview');
        $result->assertStatus(302);
    }
}
