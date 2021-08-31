<?php

namespace davidhirtz\yii2\skeleton\validators;

use Yii;
use yii\validators\Validator;

/**
 * Class GoogleAuthenticatorValidator
 * @package davidhirtz\yii2\skeleton\validators
 */
class GoogleAuthenticatorValidator extends Validator
{
    /**
     * @var string
     */
    public $secret;

    /**
     * @var int
     */
    public $length = 6;

    /**
     * @var int  the factor of periodSize ($discrepancy * $periodSize) allowed on either side of the given codePeriod.
     * For example, if a code with codePeriod = 60 is generated at 10:00:00, a discrepancy of 1 will allow a periodSize
     * of 30 seconds on either side of the codePeriod resulting in a valid code from 09:59:30 to 10:00:29.
     */
    public $discrepancy = 1;

    /**
     *
     */
    public function init()
    {
        if ($this->message === null) {
            $this->message = Yii::t('app', '{attribute} is invalid.');
        }

        parent::init();
    }

    /**
     * @param mixed $value
     * @return array|bool|void
     */
    protected function validateValue($value)
    {
        $time = floor(time() / 30);

        for ($i = -$this->discrepancy; $i <= $this->discrepancy; ++$i) {
            $code = $this->getCode($this->secret, $time + $i);
            Yii::debug($code);

            if (hash_equals($code, $value)) {
                return null;
            }
        }

        return [$this->message, []];
    }

    /**
     * @param string $secret
     * @param float $time
     * @return string
     */
    public function getCode($secret, $time)
    {
        $secret = $this->debase32($secret);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $time);

        $hm = hash_hmac('SHA1', $time, $secret, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $part = substr($hm, $offset, 4);

        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        $modulo = pow(10, $this->length);

        return str_pad($value % $modulo, $this->length, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $secret
     * @return false|string
     * @link borrowed from https://github.com/nextvikas/yii2-google-authenticator
     */
    protected function debase32($secret)
    {
        if (empty($secret)) {
            return '';
        }

        $base32chars = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '2', '3', '4', '5', '6', '7', '='];
        $base32charsFlipped = array_flip($base32chars);

        $paddingCharCount = substr_count($secret, $base32chars[32]);
        $allowedValues = [6, 4, 3, 1, 0];

        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }

        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) {
                return false;
            }
        }

        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';

        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $base32chars)) {
                return false;
            }

            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }

            $eightBits = str_split($x, 8);

            for ($z = 0; $z < count($eightBits); ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }
}
