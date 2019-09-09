<?php

namespace davidhirtz\yii2\skeleton\db;

use Yii;
use yii\validators\RequiredValidator;

/**
 * Class StatusAttributeTrait.
 * @package davidhirtz\yii2\skeleton\db
 *
 * @property int $status
 */
trait StatusAttributeTrait
{
    /**
     * Status validator.
     */
    public function validateStatus()
    {
        if ($this->status === null) {
            $this->status = static::STATUS_DEFAULT;

        } elseif (!in_array($this->status, array_keys(static::getStatuses()))) {
            $this->addInvalidAttributeError('status');
        }

        $this->status = (int)$this->status;
    }

    /**
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            static::STATUS_ENABLED => [
                'name' => Yii::t('skeleton', 'Enabled'),
                'icon' => 'globe',
            ],
            static::STATUS_DISABLED => [
                'name' => Yii::t('skeleton', 'Disabled'),
                'icon' => 'exclamation-triangle',
            ],
        ];
    }

    /**
     * @return string|null
     */
    public function getStatusName(): string
    {
        return $this->status !== null ? static::getStatuses()[$this->status]['name'] : '';
    }

    /**
     * @return string|null
     */
    public function getStatusIcon(): string
    {
        return $this->status !== null ? static::getStatuses()[$this->status]['icon'] : '';
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->status == static::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->status == static::STATUS_DISABLED;
    }
}