<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models\events;

use ArrayObject;
use yii\base\Event;
use yii\base\Model;

/**
 * @property Model $sender
 */
class CreateValidatorsEvent extends Event
{
    public const EVENT_CREATE_VALIDATORS = 'afterValidators';

    public ?ArrayObject $validators = null;
}
