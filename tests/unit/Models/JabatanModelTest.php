<?php
namespace Tests\Unit\Models;

use App\Models\JabatanModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Traits\DatabaseTrait;

final class JabatanModelTest extends CIUnitTestCase
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
        $model = new JabatanModel();
        $result = $model->findAll();
        $this->assertCount(3, $result);
    }

    public function testFind()
    {
        $model = new JabatanModel();
        $result = $model->find(1);
        $this->assertNotNull($result);
        $this->assertEquals('Chief Executive Officer', $result['jabatan']);
    }

    public function testInsert()
    {
        $model = new JabatanModel();
        $id = $model->insert(['jabatan' => 'Manager', 'slug' => 'manager']);
        $this->assertNotFalse($id);
        $this->assertEquals(4, $id);
    }

    public function testUpdate()
    {
        $model = new JabatanModel();
        $result = $model->update(1, ['jabatan' => 'CEO Updated']);
        $this->assertTrue($result);
    }

    public function testDelete()
    {
        $model = new JabatanModel();
        $result = $model->delete(3);
        $this->assertTrue($result);
        $this->assertNull($model->find(3));
    }
}