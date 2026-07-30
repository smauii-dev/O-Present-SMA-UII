<?php
namespace Tests\Unit\Models;

use App\Models\LokasiPresensiModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Traits\DatabaseTrait;

final class LokasiPresensiModelTest extends CIUnitTestCase
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
        $model = new LokasiPresensiModel();
        $result = $model->findAll();
        $this->assertCount(1, $result);
    }

    public function testGetLokasi()
    {
        $model = new LokasiPresensiModel();
        $result = $model->getLokasi('sma-uii-yogyakarta');
        $this->assertNotNull($result);
    }

    public function testInsert()
    {
        $model = new LokasiPresensiModel();
        $id = $model->insert([
            'nama_lokasi' => 'Branch Office',
            'slug' => 'branch-office',
            'alamat_lokasi' => 'Jl. Test Branch',
            'tipe_lokasi' => 'Branch Office',
            'latitude' => '-7.5000',
            'longitude' => '110.5000',
            'radius' => 300,
            'zona_waktu' => 'Asia/Jakarta',
            'jam_masuk' => '09:00',
            'jam_pulang' => '17:00',
        ]);
        $this->assertNotFalse($id);
    }
}