<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Controller;
use Stringable;
use Yii;
use yii\base\Model;

class ControllerTest extends TestCase
{
    public function testErrorOrSuccessWithModelHavingErrors(): void
    {
        $model = new Model();
        $model->addError('field', 'error');

        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess($model, 'Success message');
        $flashes = $this->getWebSession()->getAllFlashes();

        self::assertArrayHasKey('danger', $flashes);
        self::assertEquals('error', $flashes['danger'][0]['field']);
        self::assertArrayNotHasKey('success', $flashes);
    }

    public function testErrorOrSuccessWithModelWithoutErrors(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess(new Model(), 'Success message');
        $flashes = $this->getWebSession()->getAllFlashes();

        self::assertArrayHasKey('success', $flashes);
        self::assertEquals('Success message', $flashes['success'][0]);
        self::assertArrayNotHasKey('error', $flashes);
    }

    public function testErrorOrSuccessWithNonEmptyArray(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess(['error'], 'Success message');
        $flashes = $this->getWebSession()->getAllFlashes();

        self::assertArrayHasKey('danger', $flashes);
        self::assertEquals(['error'], $flashes['danger'][0]);
        self::assertArrayNotHasKey('success', $flashes);
    }

    public function testErrorOrSuccessWithEmptyArray(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->errorOrSuccess([], 'Success message');
        $flashes = $this->getWebSession()->getAllFlashes();

        self::assertArrayHasKey('success', $flashes);
        self::assertEquals('Success message', $flashes['success'][0]);
        self::assertArrayNotHasKey('error', $flashes);
    }

    /**
     * A flash is rendered as HTML, and a validation message carries the value the user typed (monorepo issue
     * #160).
     */
    public function testAStringFlashIsEncoded(): void
    {
        $model = new Model();
        $model->addError('field', 'A redirect with "<script>x</script>" already exists.');

        $controller = new Controller('test', Yii::$app);
        $controller->error($model);
        $controller->success('Plain <b>text</b>');

        $flashes = $this->getWebSession()->getAllFlashes();

        self::assertSame(
            'A redirect with &quot;&lt;script&gt;x&lt;/script&gt;&quot; already exists.',
            $flashes['danger'][0]['field'],
        );

        self::assertSame('Plain &lt;b&gt;text&lt;/b&gt;', $flashes['success'][0]);
    }

    /**
     * A `Stringable` built its own markup and knows what it escaped, so it is passed through — which is what
     * lets the media bundle link the asset it just created.
     */
    public function testAStringableFlashIsTrusted(): void
    {
        $controller = new Controller('test', Yii::$app);
        $controller->success(new class () implements Stringable {
            public function __toString(): string
            {
                return '<a href="/admin">Link</a>';
            }
        });

        self::assertSame('<a href="/admin">Link</a>', $this->getWebSession()->getAllFlashes()['success'][0]);
    }
}
