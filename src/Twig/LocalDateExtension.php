<?php

namespace App\Twig;

use App\Repository\SettingsRepository;
use Twig\Attribute\AsTwigFilter;

/**
 * This is largely taken from the twig CoreExtension to handle any edge cases I haven't considered, but otherwise
 * retrieves the timezone from the the timezone setting which is generated when the map location is saved.
 *
 * @see Twig\Extension\CoreExtension::formatDate()
 */
class LocalDateExtension
{
    private $dateFormats = ['F j, Y H:i', '%d days'];

    public function __construct(
        private SettingsRepository $settingsRepository,
    ) {
    }

    #[AsTwigFilter('local_date')]
    public function formatLocalDate($date, $format = null): string
    {
        if (null === $format) {
            $format = $date instanceof \DateInterval ? $this->dateFormats[1] : $this->dateFormats[0];
        }

        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }

        $timezone = null;
        $timezoneSetting = $this->settingsRepository->getSettingByName('timezone');
        if ($timezoneSetting) {
            $timezone = new \DateTimeZone($timezoneSetting->getValue());
        }

        if (!$timezone) {
            $timezone = new \DateTimeZone(date_default_timezone_get());
        }

        if ($date instanceof \DateTimeImmutable) {
            return false !== $timezone ? $date->setTimezone($timezone)->format($format) : $date->format($format);
        }

        if ($date instanceof \DateTime) {
            $date = clone $date;
            if (false !== $timezone) {
                $date->setTimezone($timezone);
            }

            return $date->format($format);
        }

        if (null === $date || 'now' === $date) {
            if (null === $date) {
                $date = 'now';
            }

            return new \DateTime($date, $timezone)->format($format);
        }

        $asString = (string) $date;
        if (ctype_digit($asString) || ('' !== $asString && '-' === $asString[0] && ctype_digit(substr($asString, 1)))) {
            $date = new \DateTime('@' . $date);
        } else {
            $date = new \DateTime($date);
        }

        if (false !== $timezone) {
            $date->setTimezone($timezone);
        }

        return $date;
    }
}
