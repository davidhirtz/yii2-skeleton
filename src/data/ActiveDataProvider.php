<?php

namespace davidhirtz\yii2\skeleton\data;

class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    public const EVENT_INIT = 'init';
    public const EVENT_AFTER_PREPARE = 'afterPrepare';

    public function init(): void
    {
        parent::init();

        $this->trigger(static::EVENT_INIT);
        $this->prepareQuery();
    }

    public function prepareQuery(): void
    {
        $this->trigger(static::EVENT_AFTER_PREPARE);
    }
}
