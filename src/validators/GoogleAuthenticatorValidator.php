<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\datetime\DateTime;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\NotSupportedException;
use yii\validators\StringValidator;

class GoogleAuthenticatorValidator extends StringValidator
{
    /**
     * @var string|null the Google authenticator secret key.
     */
    public ?string $secret = null;

    /**
     * @var DateTime|null the previous datetime a code was matched. Only if the returned timeslice is greater than the last used
     * datetime for this user/secret this is the first time the code has been used. This is an effective defense against a
     * replay attack. If null, the check will not be performed.
     */
    public ?DateTime $datetime = null;

    /**
     * @var int
     */
    public $length = 6;

    /**
     * @var int the factor of periodSize ($discrepancy *) allowed on either side of the given codePeriod.
     * For example, if a code with codePeriod = 60 is generated at 10:00:00, a discrepancy of 1 will allow a periodSize
     * of 30 seconds on either side of the codePeriod resulting in a valid code from 09:59:30 to 10:00:29.
     */
    public int $discrepancy = 1;

    /**
     * @var int defines the period that a TOTP code will be valid for, in seconds. The default value is 30.
     */
    public int $period = 30;

    /**
     * @var int|null allows checking a code for a specific point in time. This argument has no real practical use but is
     * used for unit testing. The default value, null, means: use the current time
     */
    public ?int $currentTime = null;

    public function init(): void
    {
        $this->message ??= Yii::t('yii', '{attribute} is invalid.');
        parent::init();
    }

    public function validateAttribute($model, $attribute): void
    {
        parent::validateAttribute($model, $attribute);

        if (!$model->hasErrors($attribute)) {
            $auth = new TwoFactorAuth(null, $this->length, $this->period);
            $timestamp = $this->datetime ? (int)floor($this->datetime->getTimestamp() / $this->period) : 0;

            if (!$auth->verifyCode($this->secret, $model->$attribute, $this->discrepancy, $this->currentTime, $timeslice) || ($timeslice <= $timestamp)) {
                $this->addError($model, $attribute, $this->message);
            }
        }
    }

    /**
     * @param mixed $value
     * @return array|bool|void
     */
    protected function validateValue($value)
    {
        throw new NotSupportedException(self::class . ' does not support validateValue().');
    }
}
