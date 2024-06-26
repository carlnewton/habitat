<?php

namespace App\Utilities;

class LatLong
{
    public static function isValidLatLong(string $latLong): bool
    {
        if (!str_contains($latLong, ',')) {
            return false;
        }

        $latLongArr = explode(',', $latLong);

        if (count($latLongArr) !== 2) {
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
            $latitude < -90 || 
            $latitude > 90 ||
            $longitude < -180 ||
            $longitude > 180
        ) {
            return false;
        }

        return true;
    }
}
