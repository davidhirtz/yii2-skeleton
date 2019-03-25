<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;

/**
 * Class DbSession.
 * @package davidhirtz\yii2\skeleton\web
 */
class DbSession extends \yii\web\DbSession
{
    /**
     * @var int
     */
    public $updateInterval = 60;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->writeCallback) {
            $this->writeCallback = function () {
                return [
                    'ip' => sprintf('%u', ip2long(Yii::$app->getRequest()->getUserIP())),
                ];
            };
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function gcSession($maxLifetime)
    {
        $this->db->createCommand()
            ->delete('session_auth_key', '[[expire]]<:expire', [':expire' => time()])
            ->execute();

        return parent::gcSession($maxLifetime);
    }
}