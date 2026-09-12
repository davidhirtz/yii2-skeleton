<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Navs\Dropdown;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use yii\base\Event;
use yii\base\Model;

class WidgetTest extends TestCase
{
    public function testConfigureEventIsTriggeredAfterDefaultsAndBeforePrepare(): void
    {
        Event::on(TestWidget::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var TestWidget $widget */
            $widget = $event->sender;
            $widget->addPart('event');
        });

        $content = TestWidget::make()
            ->prepare(static fn (TestWidget $widget) => $widget->addPart('prepare'))
            ->render();

        self::assertSame('default,event,prepare', $content);
    }

    public function testConfigureEventIsTriggeredOnSubclass(): void
    {
        Event::on(TestChildWidget::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var TestChildWidget $widget */
            $widget = $event->sender;
            $widget->addPart('event');
        });

        self::assertSame('child,default,event', TestChildWidget::make()->render());
        self::assertSame('default', TestWidget::make()->render());
    }

    public function testConfigureEventOnParentClassIsTriggeredForSubclass(): void
    {
        Event::on(TestWidget::class, Widget::EVENT_CONFIGURE, static function (Event $event): void {
            /** @var TestWidget $widget */
            $widget = $event->sender;
            $widget->addPart('event');
        });

        self::assertSame('child,default,event', TestChildWidget::make()->render());
    }

    public function testPrepareIsEvaluatedOnActiveForm(): void
    {
        $content = ActiveForm::make()
            ->model(new TestFormModel())
            ->prepare(static fn (ActiveForm $form) => $form->attribute('data-prepared', 'form'))
            ->render();

        self::assertStringContainsString('data-prepared="form"', $content);
    }

    public function testPrepareIsEvaluatedOnDropdown(): void
    {
        $content = Dropdown::make()
            ->label('Dropdown')
            ->prepare(static fn (Dropdown $dropdown) => $dropdown->addItem('Prepared'))
            ->render();

        self::assertStringContainsString('Prepared', $content);
    }
}

class TestFormModel extends Model
{
}

class TestWidget extends Widget
{
    /**
     * @var string[]
     */
    protected array $parts = [];

    public function addPart(string $part): static
    {
        $this->parts[] = $part;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->addPart('default');

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return implode(',', $this->parts);
    }
}

class TestChildWidget extends TestWidget
{
    #[Override]
    protected function configure(): void
    {
        $this->addPart('child');

        parent::configure();
    }
}
