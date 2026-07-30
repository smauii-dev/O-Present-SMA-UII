<?php

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

class GeoHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('geo');
    }

    public function testHaversineSamePoint()
    {
        $distance = haversine_distance_meters(-7.7956, 110.3695, -7.7956, 110.3695);
        $this->assertEquals(0, $distance);
    }

    public function testHaversineNearbyPoints()
    {
        $distance = haversine_distance_meters(-7.7956, 110.3695, -7.795601, 110.369501);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(1, $distance);
    }

    public function testHaversineDifferentPoints()
    {
        $distance = haversine_distance_meters(-7.7956, 110.3695, -7.8056, 110.3795);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(2000, $distance);
    }

    public function testNormalizeCoordinate()
    {
        $result = normalize_coordinate('-7.8143300');
        $this->assertEquals('-7.8143300', $result);
    }

    public function testNormalizeCoordinateNull()
    {
        $result = normalize_coordinate(null);
        $this->assertNull($result);
    }

    public function testDmsToDecimal()
    {
        $result = dms_to_decimal(7, 48, 51.3, 'S');
        $this->assertGreaterThan(-8, $result);
        $this->assertLessThan(-7, $result);
    }

    public function testParseGoogleMapsUrl()
    {
        $result = parse_google_maps_url('-7.8143300, 110.3760340');
        $this->assertNotNull($result);
        $this->assertEquals(-7.8143300, $result['lat'], '', 0.001);
        $this->assertEquals(110.3760340, $result['lng'], '', 0.001);
    }

    public function testParseGoogleMapsUrlEmpty()
    {
        $result = parse_google_maps_url('');
        $this->assertNull($result);
    }

    public function testValidateLatLng()
    {
        $result = validate_lat_lng(-7.8143300, 110.3760340);
        $this->assertNotNull($result);
        $this->assertEquals(-7.8143300, $result['lat']);
    }

    public function testValidateLatLngOutOfBounds()
    {
        $result = validate_lat_lng(100.0, 200.0);
        $this->assertNull($result);
    }
}
