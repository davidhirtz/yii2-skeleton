<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Helpers;

use Hirtz\Skeleton\Helpers\EventHelper;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\Component;
use yii\base\Event;

class EventHelperTest extends TestCase
{
    public function testHandlerReceivesSender(): void
    {
        $senders = [];

        EventHelper::on(
            EventHelperTestComponent::class,
            EventHelperTestComponent::EVENT_TEST,
            static function (EventHelperTestComponent $component) use (&$senders): void {
                $senders[] = $component->name;
            }
        );

        $component = new EventHelperTestComponent();
        $component->name = 'first';
        $component->trigger(EventHelperTestComponent::EVENT_TEST);

        self::assertSame(['first'], $senders);
    }

    public function testHandlerReceivesEventAsSecondArgument(): void
    {
        $data = null;
        $name = null;

        EventHelper::on(
            EventHelperTestComponent::class,
            EventHelperTestComponent::EVENT_TEST,
            static function (EventHelperTestComponent $component, Event $event) use (&$data, &$name): void {
                $data = $event->data;
                $name = $event->name;
            },
            'attached data'
        );

        (new EventHelperTestComponent())->trigger(EventHelperTestComponent::EVENT_TEST);

        self::assertSame('attached data', $data);
        self::assertSame(EventHelperTestComponent::EVENT_TEST, $name);
    }

    public function testHandlerOnParentClassReceivesSubclassSender(): void
    {
        $senders = [];

        EventHelper::on(
            EventHelperTestComponent::class,
            EventHelperTestComponent::EVENT_TEST,
            static function (EventHelperTestComponent $component) use (&$senders): void {
                $senders[] = $component::class;
            }
        );

        (new EventHelperTestChildComponent())->trigger(EventHelperTestComponent::EVENT_TEST);

        self::assertSame([EventHelperTestChildComponent::class], $senders);
    }
}

class EventHelperTestComponent extends Component
{
    public const string EVENT_TEST = 'test';

    public string $name = '';
}

class EventHelperTestChildComponent extends EventHelperTestComponent
{
}
