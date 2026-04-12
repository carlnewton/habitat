<?php

declare(strict_types=1);

use App\Utilities\LatLong;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LatLongTest extends TestCase
{
    private LatLong $latLong;

    protected function setUp(): void
    {
        $this->latLong = new LatLong();
    }

    public static function latLongWithinBoundsProvider(): array
    {
        return [
            'Same coordinates as the center of the boundary with 1km perimeter' => [
                '51.507368,-0.127695',
                1000,
                '51.507368,-0.127695',
                true,
            ],
            '500 meters from center of 1km perimeter' => [
                '51.507368,-0.127695',
                1000,
                '51.5113,-0.134020',
                true,
            ],
            'Inside outer edge of 1km perimeter' => [
                '51.507368,-0.127695',
                1000,
                '51.5020,-0.116522',
                true,
            ],
            'Outside outer edge of 1km perimeter' => [
                '51.507368,-0.127695',
                1000,
                '51.5081,-0.113044',
                false,
            ],
            'Large distance from 1km perimeter' => [
                '51.507368,-0.127695',
                1000,
                '39.0147,-89.7922',
                false,
            ],
            'Same coordinates as the center of the boundary with 100km perimeter' => [
                '28.4228,-17.1198',
                100000,
                '28.4228,-17.1198',
                true,
            ],
            '50km from center of 100km perimeter' => [
                '28.4228,-17.1198',
                100000,
                '28.2985,-16.6627',
                true,
            ],
            'Inside outer edge of 100km perimeter' => [
                '28.4228,-17.1198',
                100000,
                '28.5645,-16.1112',
                true,
            ],
            'Outside outer edge of 100km perimeter' => [
                '28.4228,-17.1198',
                100000,
                '27.8307,-17.8972',
                false,
            ],
            'Large distance from 100km perimeter' => [
                '28.4228,-17.1198',
                100000,
                '51.507368,-0.127695',
                false,
            ],
        ];
    }

    /**
     * We use a computationally inexpensive algorithm for calculating distances between lat/long coordinates. At short
     * distances, it's reasonably accurate, but at very long distances, it's less accurate. For instance, New York to
     * San Francisco is a few hundred meters out. I'm happy with this as it pertains to how Habitat is and isn't used.
     */
    public static function latLongDistancesProvider(): array
    {
        return [
            'Single lane road width' => [
                '51.50501322280051, -0.12478446119159979',
                '51.50500320561542, -0.12471204154629228',
                5.13,
            ],
            'Thames width' => [
                '51.50559383028142, -0.11833055617231387',
                '51.50671766937824, -0.12192512241222263',
                278.41,
            ],
            'Loch Ness length' => [
                '57.143518384483286, -4.672178112761057',
                '57.40191004810674, -4.333358130275871',
                35219.25,
            ],
            'Dover to Calais' => [
                '51.12685113716421, 1.3196230394710848',
                '50.967737982582285, 1.844526280508374',
                40736.39,
            ],
            'New York to San Francisco' => [
                '40.71460392403596, -74.01543838828364',
                '37.77280834510413, -122.43399236828671',
                4129550.93,
            ],
        ];
    }

    public static function latLongStringsToTimeZonesProvider(): array
    {
        return [
            'Africa/Cairo' => [
                '29.979210037800968, 31.13421784551751',
                'Africa/Cairo',
            ],
            'Atlantic/Reykjavik' => [
                '64.82067988868661, -18.80079657401947',
                'Atlantic/Reykjavik',
            ],
            'Europe/London' => [
                '51.17881471346535, -1.826107714491775',
                'Europe/London',
            ],
        ];
    }

    public static function latLongStringsProvider(): array
    {
        return [
            'Standard lat/long coordinates separated by a comma' => [
                '29.979210037800968, 31.13421784551751',
                [
                    'latitude' => 29.979210037800968,
                    'longitude' => 31.13421784551751,
                ],
            ],
            'Lat/long coordinates separated by a comma without a space' => [
                '29.979210037800968,31.13421784551751',
                [
                    'latitude' => 29.979210037800968,
                    'longitude' => 31.13421784551751,
                ],
            ],
            'Lat/long coordinates with spaces' => [
                '    29.979210037800968    ,    31.13421784551751    ',
                [
                    'latitude' => 29.979210037800968,
                    'longitude' => 31.13421784551751,
                ],
            ],
            'Lat/long coordinates with fewer digits' => [
                '29.979210, 31.134217',
                [
                    'latitude' => 29.979210,
                    'longitude' => 31.134217,
                ],
            ],
            'Lat/long coordinates with whole numbers' => [
                '30, 31',
                [
                    'latitude' => 30.0,
                    'longitude' => 31.0,
                ],
            ],
            'Invalid lat/long coordinates with lower than valid latitude' => [
                '-99.999999, 31.134217',
                null,
            ],
            'Invalid lat/long coordinates with higher than valid latitude' => [
                '99.999999, 31.134217',
                null,
            ],
            'Invalid lat/long coordinates with lower than valid longitude' => [
                '29.979210, -222.222222',
                null,
            ],
            'Invalid lat/long coordinates with higher than valid longitude' => [
                '29.979210, 222.222222',
                null,
            ],
            'Invalid lat/long coordinates with three coordinates' => [
                '29.979210, 31.134217, 22.222222',
                null,
            ],
            'Invalid lat/long coordinates with one coordinate' => [
                '29.979210',
                null,
            ],
            'Invalid lat/long coordinates with one coordinate and a comma' => [
                '29.979210,',
                null,
            ],
            'Empty lat/long coordinates with a comma' => [
                ',',
                null,
            ],
            'Alphabetic characters separated by a comma' => [
                'latitude, longitude',
                null,
            ],
        ];
    }

    #[DataProvider('latLongStringsProvider')]
    public function testFromStringMethodReturnsLatLongCoordinates(
        string $latLongString,
        ?array $expectedCoordinates,
    ): void {
        $result = $this->latLong->fromString($latLongString);

        if ($expectedCoordinates) {
            $this->assertInstanceOf(LatLong::class, $result);
            $this->assertSame($result->latitude, $expectedCoordinates['latitude']);
            $this->assertSame($result->longitude, $expectedCoordinates['longitude']);
        } else {
            $this->assertNull($result);
        }
    }

    #[DataProvider('latLongStringsProvider')]
    public function testToStringMagicMethodReturnsLatLongCoordinatesString(
        string $latLongString,
        ?array $expectedCoordinates,
    ): void {
        $result = $this->latLong->fromString($latLongString);

        if ($expectedCoordinates) {
            $expectedString = $expectedCoordinates['latitude'] . ',' . $expectedCoordinates['longitude'];
        } else {
            $expectedString = '';
        }

        $this->assertSame((string) $result, $expectedString);
    }

    #[DataProvider('latLongStringsToTimeZonesProvider')]
    public function testGetTimezoneMethodReturnsTimeZone(string $latLongString, string $expectedTimeZone): void
    {
        $result = $this->latLong->getTimezone($latLongString);

        $this->assertSame($result->getName(), $expectedTimeZone);
    }

    #[DataProvider('latLongDistancesProvider')]
    public function testGetDistanceReturnsDistanceInMeters(
        string $latLongStringA,
        string $latLongStringB,
        float $expectedRoundedDistanceInMeters,
    ): void {
        $result = $this->latLong->getDistance($latLongStringA, $latLongStringB);

        $roundedResult = round($result, 2);

        $this->assertSame($roundedResult, $expectedRoundedDistanceInMeters);
    }

    #[DataProvider('latLongWithinBoundsProvider')]
    public function testIsValidLatLongDeterminesWhetherCoordinatesAreInsideBounds(
        string $boundaryLatLongString,
        int $boundarySizeMeters,
        string $locationWithinBoundsLatLongString,
        bool $isWithinBounds,
    ): void {
        $result = $this->latLong->isValidLatLong($locationWithinBoundsLatLongString, $boundaryLatLongString, $boundarySizeMeters);

        $this->assertSame($result, $isWithinBounds);
    }
}
