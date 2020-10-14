<?php

namespace davidhirtz\yii2\skeleton\web;

use davidhirtz\yii2\skeleton\models\SessionAuthKey;
use Yii;

/**
 * Class DbSession
 * @package davidhirtz\yii2\skeleton\web
 */
class DbSession extends \yii\web\DbSession
{
    /**
     * @var int not yet implemented
     */
    public $updateInterval = 60;

    /**
     * @var string the optional cookie domain.
     */
    public $cookieDomain;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->writeCallback) {
            $this->writeCallback = function () {
                return [
                    'ip_address' => inet_pton(Yii::$app->getRequest()->getUserIP()),
                ];
            };
        }

        if($this->cookieDomain === null && isset(Yii::$app->params['cookieDomain'])) {
            $this->cookieDomain = Yii::$app->params['cookieDomain'];
        }

        if($this->cookieDomain) {
            $this->setCookieParams(array_merge($this->getCookieParams(), [
                'domain' => $this->cookieDomain,
            ]));
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function gcSession($maxLifetime)
    {
        $this->db->createCommand()
            ->delete(SessionAuthKey::tableName(), '[[expire]]<:expire', [':expire' => time()])
            ->execute();

        return parent::gcSession($maxLifetime);
    }
}