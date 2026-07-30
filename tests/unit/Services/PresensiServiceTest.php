<?php

namespace Tests\Unit\Services;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\PresensiService;

class PresensiServiceTest extends CIUnitTestCase
{
    public function testCalculateWorkStatsNormal()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '07:00:00', '2026-07-14', '16:00:00', '07:00:00');
        $this->assertStringContainsString('Jam', $result['jam_kerja']);
    }

    public function testCalculateWorkStatsEarly()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '06:50:00', '2026-07-14', '16:00:00', '07:00:00');
        $this->assertEquals('On Time', $result['keterlambatan']);
    }

    public function testCalculateWorkStatsWithMinutes()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '07:30:00', '2026-07-14', '16:45:00', '07:00:00');
        $this->assertStringContainsString('Jam', $result['jam_kerja']);
        $this->assertStringContainsString('Menit', $result['keterlambatan']);
    }

    public function testCalculateWorkStatsCrossDay()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '23:00:00', '2026-07-15', '07:00:00', '07:00:00');
        $this->assertStringContainsString('Jam', $result['jam_kerja']);
    }

    public function testCalculateWorkStatsZeroDuration()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '08:00:00', '2026-07-14', '08:00:00', '07:00:00');
        $this->assertEquals('0 Jam 0 Menit', $result['jam_kerja']);
    }

    public function testCalculateWorkStatsLate()
    {
        $result = PresensiService::calculateWorkStats('2026-07-14', '07:30:00', '2026-07-14', '16:30:00', '07:00:00');
        $this->assertStringContainsString('Jam', $result['jam_kerja']);
        $this->assertStringContainsString('30 Menit', $result['keterlambatan']);
    }
}
