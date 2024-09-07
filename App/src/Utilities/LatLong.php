<?php

namespace App\Utilities;

class LatLong
{
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

    public static function isValidLatLong(string $latLong): bool
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

        return true;
    }
}
