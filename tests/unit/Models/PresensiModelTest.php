<?php
namespace Tests\Unit\Models;

use App\Models\PresensiModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Traits\DatabaseTrait;

final class PresensiModelTest extends CIUnitTestCase
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
        $model = new PresensiModel();
        $this->seedPresensiData();

        $result = $model->findAll();
        $this->assertNotEmpty($result);
    }

    public function testFindAllCount()
    {
        $model = new PresensiModel();
        $this->seedPresensiData();

        $count = $model->countAllResults();
        $this->assertEquals(1, $count);
    }

    public function testFindAllNotFound()
    {
        $model = new PresensiModel();
        $result = $model->where('id_pegawai', 999)->findAll();
        $this->assertEmpty($result);
    }

    public function testInsertAttendance()
    {
        $model = new PresensiModel();
        $id = $model->insert([
            'id_pegawai' => 1,
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => '09:00:00',
            'foto_masuk' => 'photo.jpg',
        ]);
        $this->assertNotFalse($id);
    }

    public function testGetMinYear()
    {
        $model = new PresensiModel();
        $this->seedPresensiData();
        $year = $model->getMinYear();
        $this->assertEquals(date('Y'), (string)$year);
    }
}