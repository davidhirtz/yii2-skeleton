<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Data;

use Hirtz\Skeleton\Db\ActiveRecord;

/**
 * @template T of ActiveRecord
 */
class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    public const EVENT_INIT = 'init';
    public const EVENT_AFTER_PREPARE = 'afterPrepare';

    #[\Override]
    public function init(): void
    {
        parent::init();

        $this->trigger(static::EVENT_INIT);
        $this->prepareQuery();
    }

    protected function prepareQuery(): void
    {
        $this->trigger(static::EVENT_AFTER_PREPARE);
    }

    /**
     * @return T[]
     */
    #[\Override]
    public function getModels(): array
    {
        return parent::getModels();
    }
}
