<?php

namespace davidhirtz\yii2\skeleton\validators;

use davidhirtz\yii2\datetime\DateTime;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\validators\StringValidator;

/**
 * Class GoogleAuthenticatorValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class GoogleAuthenticatorValidator extends StringValidator
{
    /**
     * @var string the Google authenticator secret key.
     */
    public $secret;

    /**
     * @var DateTime|null the previous datetime a code was matched. Only if the returned timeslice is greater than the last used
     * datetime for this user/secret this is the first time the code has been used. This is an effective defense against a
     * replay attack. If null, the check will not be performed.
     */
    public $datetime;

    /**
     * @var int
     */
    public $length = 6;

    /**
     * @var int the factor of periodSize ($discrepancy * $periodSize) allowed on either side of the given codePeriod.
     * For example, if a code with codePeriod = 60 is generated at 10:00:00, a discrepancy of 1 will allow a periodSize
     * of 30 seconds on either side of the codePeriod resulting in a valid code from 09:59:30 to 10:00:29.
     */
    public $discrepancy = 1;

    /**
     * @var int defines the period that a TOTP code will be valid for, in seconds. The default value is 30.
     */
    public $period = 30;

    /**
     * @var int|null allows to check a code for a specific point in time. This argument has no real practical use but can be
     * handy for unit testing. The default value, null, means: use the current time
     */
    public $time;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->message === null) {
            $this->message = Yii::t('app', '{attribute} is invalid.');
        }

        parent::init();
    }

    /**
     * @param Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        parent::validateAttribute($model, $attribute);

        if (!$model->hasErrors($attribute)) {
            $auth = new TwoFactorAuth(null, $this->length, $this->period);
            $timestamp = $this->datetime ? (int)floor($this->datetime->getTimestamp() / $this->period) : 0;

            if (!$auth->verifyCode($this->secret, $model->$attribute, $this->discrepancy, $this->time, $timeslice) || ($timeslice <= $timestamp)) {
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
        throw new NotSupportedException(__CLASS__ . ' does not support validateValue().');
    }
}
