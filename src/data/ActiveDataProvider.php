<?php

namespace davidhirtz\yii2\skeleton\data;

/**
 * Extends {@see \yii\data\ActiveDataProvider} by providing events for init and
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{

    public const EVENT_INIT = 'init';
    public const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * Triggers `init` event after initialization is done.
     */
    public function init()
    {
        parent::init();

        $this->trigger(static::EVENT_INIT);
        $this->prepareQuery();
    }

    /**
     * Additional method to allow behaviors attached on `init` to manipulate the `query`.
     */
    public function prepareQuery()
    {
        $this->trigger(static::EVENT_AFTER_PREPARE);
    }
}