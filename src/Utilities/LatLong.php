<?php

namespace App\Utilities;

class LatLong
{
    public const EARTH_RADIUS_METERS = 6371000;

    public float $latitude;
    public float $longitude;

    public function fromString(string $latLong): ?self
    {
        if (!self::isValidLatLong($latLong)) {
            return null;
        }

        $this->latitude = floatval(explode(',', $latLong)[0]);
        $this->longitude = floatval(explode(',', $latLong)[1]);

        return $this;
    }

    public function __toString(): string
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return '';
        }

        return $this->latitude . ',' . $this->longitude;
    }

    public static function isValidLatLong(string $latLong, ?string $withinBoundsLatLong = null, ?int $withinBoundsRadiusMeters = null): bool
    {
        if (!str_contains($latLong, ',')) {
            return false;
        }

        $latLongArr = explode(',', $latLong);

        if (2 !== count($latLongArr)) {
            return false;
        }

        $latitude = $latLongArr[0];
        $longitude = $latLongArr[1];

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return false;
        }

        if ($latitude != floatval($latitude) || $longitude != floatval($longitude)) {
            return false;
        }

        $latitude = floatval($latitude);
        $longitude = floatval($longitude);

        if (
            $latitude < -90
            || $latitude > 90
            || $longitude < -180
            || $longitude > 180
        ) {
            return false;
        }

        if (null === $withinBoundsLatLong || null === $withinBoundsRadiusMeters) {
            return true;
        }

        // The Haversine Formula
        $latitude = deg2rad($latitude);
        $longitude = deg2rad($longitude);

        $withinBoundsLatLongArr = explode(',', $withinBoundsLatLong);
        $withinBoundsLatitude = deg2rad($withinBoundsLatLongArr[0]);
        $withinBoundsLongitude = deg2rad($withinBoundsLatLongArr[1]);

        $latitudeDistance = $withinBoundsLatitude - $latitude;
        $longitudeDistance = $withinBoundsLongitude - $longitude;

        $a = sin($latitudeDistance / 2) * sin($latitudeDistance / 2)
            + cos($latitude) * cos($withinBoundsLatitude)
            * sin($longitudeDistance / 2) * sin($longitudeDistance / 2)
        ;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = self::EARTH_RADIUS_METERS * $c;

        return $distance <= $withinBoundsRadiusMeters;
    }
}
