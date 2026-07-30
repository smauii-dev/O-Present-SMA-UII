<?php
namespace Tests\Unit\Models;

use App\Models\KetidakhadiranModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Traits\DatabaseTrait;

final class KetidakhadiranModelTest extends CIUnitTestCase
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
        $model = new KetidakhadiranModel();
        $result = $model->findAll();
        $this->assertIsArray($result);
    }

    public function testInsert()
    {
        $model = new KetidakhadiranModel();
        $id = $model->insert([
            'id_pegawai' => 3,
            'tipe_ketidakhadiran' => 'IZIN',
            'tanggal_mulai' => date('Y-m-d'),
            'tanggal_berakhir' => date('Y-m-d'),
            'deskripsi' => 'Test permission',
            'file' => 'test.pdf',
            'status_pengajuan' => 'PENDING',
        ]);
        $this->assertNotFalse($id);
    }
}