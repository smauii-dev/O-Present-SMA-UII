<?php
namespace Tests\Unit\Models;

use App\Models\UsersModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Traits\DatabaseTrait;

final class UsersModelTest extends CIUnitTestCase
{
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

    public function testFindAll()
    {
        $model = new UsersModel();
        $result = $model->findAll();
        $this->assertCount(3, $result);
    }

    public function testHashPassword()
    {
        $model = new UsersModel();
        $hash = $model->hashPassword('123456');
        $this->assertNotEmpty($hash);
        $this->assertNotEquals('123456', $hash);
    }

    public function testGetUserInfo()
    {
        $model = new UsersModel();
        $user = $model->getUserInfo(1);
        $this->assertNotNull($user);
    }
}
