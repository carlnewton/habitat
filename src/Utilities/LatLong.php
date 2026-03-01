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

    /**
     * This calculates distances between the given latlong and every timezone and returns the closest. That sounds like
     * a great deal of work, so I've decided not to use it dynamically and only call it when saving its returned value
     * for later use. All things considered though, it seems to be fast, but it's probably best to benchmark this if
     * ever using it on a regular old GET request.
     */
    public static function getTimezone(string $latLong): \DateTimeZone
    {
        $timezoneIdentifiers = \DateTimeZone::listIdentifiers();

        $closestTimezone = null;
        $closestDistance = null;
        foreach ($timezoneIdentifiers as $timezoneIdentifier) {
            $timezone = new \DateTimeZone($timezoneIdentifier);
            $location = timezone_location_get($timezone);
            $distance = self::getDistance($location['latitude'] . ',' . $location['longitude'], $latLong);
            if (null === $closestDistance || $closestDistance > $distance) {
                $closestDistance = $distance;
                $closestTimezone = $timezone;
            }
        }

        return $closestTimezone;
    }

    public static function getDistance(string $latLongA, string $latLongB): float
    {
        $latLongArrA = explode(',', $latLongA);
        $latitudeA = deg2rad($latLongArrA[0]);
        $longitudeA = deg2rad($latLongArrA[1]);

        $latLongArrB = explode(',', $latLongB);
        $latitudeB = deg2rad($latLongArrB[0]);
        $longitudeB = deg2rad($latLongArrB[1]);

        $latitudeDistance = $latitudeA - $latitudeB;
        $longitudeDistance = $longitudeA - $longitudeB;

        $a = sin($latitudeDistance / 2) * sin($latitudeDistance / 2)
            + cos($latitudeA) * cos($latitudeB)
            * sin($longitudeDistance / 2) * sin($longitudeDistance / 2)
        ;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = self::EARTH_RADIUS_METERS * $c;

        return $distance;
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

        $distance = self::getDistance($latitude . ',' . $longitude, $withinBoundsLatLong);

        return $distance <= $withinBoundsRadiusMeters;
    }
}
