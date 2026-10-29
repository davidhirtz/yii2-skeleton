<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Hirtz\Skeleton\Db\DateTime;
use Override;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\NotSupportedException;
use yii\validators\StringValidator;

class TwoFactorAuthenticationValidator extends StringValidator
{
    public ?string $secret = null;

    /**
     * A code must match a later timeslice than the one this time falls in.
     */
    public ?DateTime $datetime = null;

    /**
     * A code must match a later timeslice than this, since the previous login time is no replay defense when a code
     * may match a slice up to {@see $discrepancy} ahead.
     */
    public ?int $timeslice = null;

    /**
     * @var int
     */
    public $length = 6;

    public int $discrepancy = 1;

    public int $period = 30;

    public ?int $currentTime = null;

    #[Override]
    public function init(): void
    {
        if (!$this->message) {
            $this->message = Yii::t('yii', '{attribute} is invalid.');
        }

        parent::init();
    }

    #[Override]
    public function validateAttribute($model, $attribute): void
    {
        parent::validateAttribute($model, $attribute);

        if (!$model->hasErrors($attribute)) {
            $auth = new TwoFactorAuth(new BaconQrCodeProvider(), digits: $this->length, period: $this->period);
            $last = max($this->timeslice ?? 0, $this->datetime ? (int)floor($this->datetime->getTimestamp() / $this->period) : 0);

            if (!$auth->verifyCode($this->secret, $model->$attribute, $this->discrepancy, $this->currentTime, $timeslice) || ($timeslice <= $last)) {
                $this->addError($model, $attribute, $this->message);
                return;
            }

            $this->timeslice = $timeslice;
        }
    }

    /**
     * @param mixed $value
     * @return array{string, array<string, mixed>}|null
     */
    #[Override]
    protected function validateValue($value)
    {
        throw new NotSupportedException(self::class . ' does not support validateValue().');
    }
}
