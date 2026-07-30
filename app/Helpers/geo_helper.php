<?php

if (! function_exists('haversine_distance_meters')) {
    /**
     * Distance between two WGS84 points (Haversine), in meters.
     */
    function haversine_distance_meters(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $c * 6371000;
    }
}

if (! function_exists('normalize_coordinate')) {
    /**
     * Normalize a lat/lng value to a fixed-precision string for storage & uniqueness.
     * 7 decimal places ≈ 1.1 cm — enough for attendance geofencing.
     */
    function normalize_coordinate(float|string|null $value, int $precision = 7): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, $precision, '.', '');
    }
}

if (! function_exists('parse_google_maps_url')) {
    /**
     * Extract WGS84 coordinates from a Google Maps URL or free-text "lat,lng".
     *
     * Supported patterns (priority order):
     *  1. Place pin: !3d{lat}!4d{lng}
     *  2. Camera center: @{lat},{lng}
     *  3. Query: ?q={lat},{lng} / ll= / destination=
     *  4. DMS place path: 7°48'51.3"S+110°22'34.2"E
     *  5. Plain "lat,lng" pair
     *
     * @return array{lat: float, lng: float}|null
     */
    function parse_google_maps_url(string $input): ?array
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        // Decode HTML entities & plus-as-space (common when pasting)
        $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = urldecode($input);

        // 1) Explicit place marker (most accurate for "dropped pin")
        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $decoded, $m)) {
            return validate_lat_lng((float) $m[1], (float) $m[2]);
        }

        // 2) @lat,lng[,zoom]
        if (preg_match('/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/', $decoded, $m)) {
            return validate_lat_lng((float) $m[1], (float) $m[2]);
        }

        // 3) q= / ll= / destination= query params
        if (preg_match('/(?:[?&](?:q|ll|destination|center)=)(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/i', $decoded, $m)) {
            return validate_lat_lng((float) $m[1], (float) $m[2]);
        }

        // 4) DMS in path: 7°48'51.3"S+110°22'34.2"E (also works with %C2%B0 etc. after decode)
        if (preg_match(
            '/(\d{1,3})[°\s]+(\d{1,2})[\'’\s]+(\d{1,2}(?:\.\d+)?)[\"”]?\s*([NS])\s*[,+\s]+(\d{1,3})[°\s]+(\d{1,2})[\'’\s]+(\d{1,2}(?:\.\d+)?)[\"”]?\s*([EW])/iu',
            $decoded,
            $m
        )) {
            $lat = dms_to_decimal((int) $m[1], (int) $m[2], (float) $m[3], strtoupper($m[4]));
            $lng = dms_to_decimal((int) $m[5], (int) $m[6], (float) $m[7], strtoupper($m[8]));

            return validate_lat_lng($lat, $lng);
        }

        // 5) Bare pair (with optional spaces)
        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/', $decoded, $m)) {
            return validate_lat_lng((float) $m[1], (float) $m[2]);
        }

        return null;
    }
}

if (! function_exists('dms_to_decimal')) {
    function dms_to_decimal(int $deg, int $min, float $sec, string $hemisphere): float
    {
        $decimal = abs($deg) + ($min / 60) + ($sec / 3600);
        if (in_array($hemisphere, ['S', 'W'], true)) {
            $decimal *= -1;
        }

        return $decimal;
    }
}

if (! function_exists('validate_lat_lng')) {
    /**
     * @return array{lat: float, lng: float}|null
     */
    function validate_lat_lng(float $lat, float $lng): ?array
    {
        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return null;
        }

        // Reject null-island noise
        if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}

if (! function_exists('canonical_sma_uii_locations')) {
    /**
     * Official attendance points for SMA UII Yogyakarta.
     * Coordinates taken from Google Maps place pins (3d/4d).
     *
     * @return list<array<string, mixed>>
     */
    function canonical_sma_uii_locations(): array
    {
        $alamat = 'Jl. Taman Siswa No.158, Wirogunan, Kec. Mergangsan, Kota Yogyakarta, DIY 55151';

        return [
            [
                'nama_lokasi'   => 'SMA UII Yogyakarta',
                'slug'          => 'sma-uii-yogyakarta',
                'alamat_lokasi' => $alamat,
                'tipe_lokasi'   => 'Pusat',
                // Campus place pin
                'latitude'      => normalize_coordinate('-7.8143300'),
                'longitude'     => normalize_coordinate('110.3760340'),
                'radius'        => 120,
                'zona_waktu'    => 'Asia/Jakarta',
                'jam_masuk'     => '07:00:00',
                'jam_pulang'    => '15:30:00',
            ],
            [
                'nama_lokasi'   => 'SMA UII Gedung Depan',
                'slug'          => 'sma-uii-gedung-depan',
                'alamat_lokasi' => $alamat . ' (Gedung Depan)',
                'tipe_lokasi'   => 'Pusat',
                // https://www.google.com/maps/place/... !3d-7.814236!4d110.376172
                'latitude'      => normalize_coordinate('-7.8142360'),
                'longitude'     => normalize_coordinate('110.3761720'),
                'radius'        => 80,
                'zona_waktu'    => 'Asia/Jakarta',
                'jam_masuk'     => '07:00:00',
                'jam_pulang'    => '15:30:00',
            ],
            [
                'nama_lokasi'   => 'SMA UII Gedung Belakang',
                'slug'          => 'sma-uii-gedung-belakang',
                'alamat_lokasi' => $alamat . ' (Gedung Belakang)',
                'tipe_lokasi'   => 'Pusat',
                // !3d-7.814359!4d110.37578
                'latitude'      => normalize_coordinate('-7.8143590'),
                'longitude'     => normalize_coordinate('110.3757800'),
                'radius'        => 80,
                'zona_waktu'    => 'Asia/Jakarta',
                'jam_masuk'     => '07:00:00',
                'jam_pulang'    => '15:30:00',
            ],
        ];
    }
}
