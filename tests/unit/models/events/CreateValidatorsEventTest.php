<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\models\events;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\events\CreateValidatorsEvent;
use yii\base\Behavior;
use yii\base\Model;
use yii\validators\RequiredValidator;

class CreateValidatorsEventTest extends Unit
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
