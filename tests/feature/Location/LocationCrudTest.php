<?php
namespace Tests\Feature\Location;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\{AuthTrait, DatabaseTrait};

final class LocationCrudTest extends CIUnitTestCase
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
        $result = $this->get('/admin/lokasi');
        $result->assertStatus(200);
    }

    public function testFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/lokasi/form');
        $result->assertStatus(200);
    }

    public function testEditFormReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/lokasi/form/1');
        $result->assertStatus(200);
    }

    public function testFragmentReturns200()
    {
        $this->loginAsAdmin();
        $result = $this->get('/admin/lokasi/fragment');
        $result->assertStatus(200);
    }

    public function testDeleteLokasi()
    {
        $this->loginAsAdmin();
        $db = \Config\Database::connect();
        $id = $db->table('lokasi_presensi')->insert([
            'nama_lokasi' => 'To Delete', 'slug' => 'to-delete',
            'alamat_lokasi' => 'Test', 'tipe_lokasi' => 'Branch Office',
            'latitude' => '-7.5', 'longitude' => '110.5',
            'radius' => 100, 'zona_waktu' => 'Asia/Jakarta',
            'jam_masuk' => '08:00:00', 'jam_pulang' => '17:00:00',
        ]);

        $result = $this->delete('/admin/lokasi/' . $id);
        $this->assertContains($result->response()->getStatusCode(), [200, 302, 409]);
    }
}
