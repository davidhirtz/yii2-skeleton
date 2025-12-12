<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Events;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Events\CreateValidatorsEvent;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\Behavior;
use yii\base\Model;
use yii\validators\RequiredValidator;

class CreateValidatorsEventTest extends TestCase
{
    public function testCreateValidatorsEvent(): void
    {
        $model = new TestModel();
        self::assertFalse($model->isAttributeRequired('test'));

        $model = new TestModel();
        $model->attachBehavior('CreateValidatorsEventBehavior', CreateValidatorsEventBehavior::class);
        self::assertTrue($model->isAttributeRequired('test'));
    }
}

class TestModel extends Model
{
    use ModelTrait;
    public ?string $test = null;
}

class CreateValidatorsEventBehavior extends Behavior
{
    #[\Override]
    public function events(): array
    {
        return [
            CreateValidatorsEvent::EVENT_CREATE_VALIDATORS => $this->onCreateValidators(...),
        ];
    }

    public function onCreateValidators(CreateValidatorsEvent $event): void
    {
        $event->validators->append(new RequiredValidator([
            'attributes' => ['test'],
        ]));
    }
}
