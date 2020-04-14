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
     * @
     * @var int
     */
    public $updateInterval = 60;

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