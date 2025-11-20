<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use DateTime;
use DateTimeZone;
use Stringable;

class TimezoneSelectField extends SelectField
{
    private ?array $_timezones = null;

    protected function renderContent(): string|Stringable
    {
        $this->items = $this->getTimezones();
        return parent::renderContent();
    }

    public function getTimezones(): array
    {
        if ($this->_timezones === null) {
            $identifiers = DateTimeZone::listIdentifiers();
            $now = new DateTime('now', new DateTimeZone('GMT'));
            $list = [];

            // Create a multidimensional array to sort by offset first and display name after.
            foreach (DateTimeZone::listAbbreviations() as $timezones) {
                foreach ($timezones as $tz) {
                    if (!empty($tz['timezone_id']) && in_array($tz['timezone_id'], $identifiers, true)) {
                        $timezone = new DateTimeZone($tz['timezone_id']);
                        $list[$timezone->getOffset($now)][$tz['timezone_id']] = $this->formatTimezoneName($tz['timezone_id']);
                    }
                }
            }

            ksort($list);

            foreach ($list as $offset => $timezones) {
                asort($timezones);

                foreach ($timezones as $name => $displayName) {
                    $this->_timezones[$name] = $this->formatTimezoneOffset($offset) . ' | ' . $displayName;
                }
            }

            asort($this->_timezones);
        }

        return $this->_timezones;
    }

    private function formatTimezoneOffset(int $offset): string
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours < 0 ? '-' : '+';
        $hour = (int)abs($hours);
        $minutes = (int)abs($remainder / 60);

        return "GMT$sign"
            . str_pad((string)$hour, 2, '0', STR_PAD_LEFT)
            . ':' . str_pad((string)$minutes, 2, '0');
    }

    private function formatTimezoneName(string $name): string
    {
        return match ($name) {
            'America/Anchorage' => 'Alaska',
            'America/Los_Angeles' => 'Pacific Time (PST)',
            'America/Denver' => 'Mountain Time (MST)',
            'America/Dawson_Creek' => 'Arizona',
            'America/Chicago' => 'Central Time (CST)',
            'America/New_York' => 'Eastern Time (EST)',
            'America/Campo_Grande' => 'Brazil',
            default => strtr(preg_replace('#^(America|Arctic|Asia|Atlantic|Australia|Europe|Indian)/#', '', $name), ['_' => ' ', '/' => ' | ']),
        };
    }
}
