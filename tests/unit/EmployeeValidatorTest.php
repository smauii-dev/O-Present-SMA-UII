<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Validation\PegawaiRules;
use Tests\Support\Traits\DatabaseTrait;

final class PegawaiRulesTest extends CIUnitTestCase
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

    public function testCreateRulesReturnArray(): void
    {
        $rules = PegawaiRules::create();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('nama', $rules);
        $this->assertArrayHasKey('jenis_kelamin', $rules);
        $this->assertArrayHasKey('alamat', $rules);
        $this->assertArrayHasKey('no_handphone', $rules);
        $this->assertArrayHasKey('jabatan', $rules);
        $this->assertArrayHasKey('lokasi_presensi', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('role', $rules);
    }

    public function testUpdateRulesReturnArray(): void
    {
        $rules = PegawaiRules::update(true, true);

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('nama', $rules);
        $this->assertArrayHasKey('jenis_kelamin', $rules);
        $this->assertArrayHasKey('alamat', $rules);
        $this->assertArrayHasKey('no_handphone', $rules);
        $this->assertArrayHasKey('jabatan', $rules);
        $this->assertArrayHasKey('lokasi_presensi', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('role', $rules);
    }

    public function testProfileUpdateRulesReturnArray(): void
    {
        $rules = PegawaiRules::profileUpdate();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('nama', $rules);
        $this->assertArrayHasKey('no_handphone', $rules);
        $this->assertArrayHasKey('alamat', $rules);
        $this->assertArrayHasKey('jenis_kelamin', $rules);
    }

    public function testPhoneRule(): void
    {
        $rule = PegawaiRules::phone();

        $this->assertIsString($rule);
        $this->assertStringContainsString('regex_match', $rule);
    }

    public function testGenderRule(): void
    {
        $rule = PegawaiRules::gender();

        $this->assertIsString($rule);
        $this->assertStringContainsString('in_list', $rule);
        $this->assertStringContainsString('Perempuan', $rule);
        $this->assertStringContainsString('Laki-laki', $rule);
    }
}