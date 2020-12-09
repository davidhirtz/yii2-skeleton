<?php

namespace davidhirtz\yii2\skeleton\widgets\forms;

use DateTime;
use DateTimeZone;
use Exception;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Class TimezoneDropDownList.
 * @package davidhirtz\yii2\skeleton\widgets\form
 *
 * @property array $timezones
 * @see TimezoneDropdown::getTimezones()
 */
class TimezoneDropdown extends InputWidget
{
    /**
     * @var array
     */
    public $options = ['class' => 'form-control'];

    /**
     * @see TimezoneDropdown::getTimezones()
     * @var array
     */
    private $_timezones;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $timezones = $this->getTimezones();
        return $this->hasModel() ? Html::activeDropDownList($this->model, $this->attribute, $timezones, $this->options) : Html::dropDownList($this->name, $this->value, $timezones, $this->options);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTimezones()
    {
        if ($this->_timezones === null) {
            $identifiers = DateTimeZone::listIdentifiers();
            $now = new DateTime('now', new DateTimeZone('GMT'));
            $list = [];

            // Create a multi dimensional array to sort by offset first  and display name after.
            foreach (DateTimeZone::listAbbreviations() as $timezones) {
                foreach ($timezones as $tz) {
                    if (!empty($tz['timezone_id']) && in_array($tz['timezone_id'], $identifiers)) {
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

    /**
     * @param array $timezones
     */
    public function setTimezones($timezones)
    {
        $this->_timezones = $timezones;
    }

    /**
     * @param int $offset
     * @return string the formatted timezone offset.
     */
    private function formatTimezoneOffset($offset)
    {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours < 0 ? '-' : '+';
        $hour = (int)abs($hours);
        $minutes = (int)abs($remainder / 60);

        return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0');

    }

    /**
     * @param string $name
     * @return string the formatted timezone name.
     */
    private function formatTimezoneName($name)
    {
        switch ($name) {
            case 'America/Anchorage':
                return 'Alaska';
            case 'America/Los_Angeles':
                return 'Pacific Time (PST)';
            case 'America/Denver':
                return 'Mountain Time (MST)';
            case 'America/Dawson_Creek':
                return 'Arizona';
            case 'America/Chicago':
                return 'Central Time (CST)';
            case 'America/New_York':
                return 'Eastern Time (EST)';
            case 'America/Campo_Grande':
                return 'Brazil';
        }

        return strtr(preg_replace('#^(America|Arctic|Asia|Atlantic|Australia|Europe|Indian)/#', '', $name), array(
            '_' => ' ',
            '/' => ' | '
        ));
    }
}