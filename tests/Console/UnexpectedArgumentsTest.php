<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use yii\base\Action;
use yii\console\Controller;
use yii\console\Exception;

/**
 * An argument the action does not take is otherwise dropped without a word: `transformation/delete name jpg`, meant
 * as `--extension=jpg`, deleted every extension of the transformation.
 */
class UnexpectedArgumentsTest extends TestCase
{
    protected string $applicationClass = Application::class;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Application::current()->controllerMap['unexpected-arguments'] = UnexpectedArgumentsController::class;
    }

    public function testAnArgumentTheActionTakesIsPassed(): void
    {
        self::assertSame(0, Application::current()->runAction('unexpected-arguments/one', ['a']));
        self::assertSame(['a'], UnexpectedArgumentsController::$received);
    }

    public function testAnArgumentTheActionDoesNotTakeIsRefused(): void
    {
        UnexpectedArgumentsController::$received = null;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unexpected arguments: b, c');

        try {
            Application::current()->runAction('unexpected-arguments/one', ['a', 'b', 'c']);
        } finally {
            self::assertNull(UnexpectedArgumentsController::$received);
        }
    }

    public function testAnOptionIsNotAnArgument(): void
    {
        self::assertSame(0, Application::current()->runAction('unexpected-arguments/one', ['a', 'option' => 'b']));
        self::assertSame(['a', 'b'], UnexpectedArgumentsController::$received);
    }

    public function testAVariadicActionTakesEveryArgument(): void
    {
        self::assertSame(0, Application::current()->runAction('unexpected-arguments/many', ['a', 'b', 'c']));
        self::assertSame(['a', 'b', 'c'], UnexpectedArgumentsController::$received);
    }

    public function testAStandaloneActionIsCheckedToo(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unexpected arguments: b');

        Application::current()->runAction('unexpected-arguments/standalone', ['a', 'b']);
    }

    /**
     * The arguments belong to the route the application was asked to run; a controller run directly afterwards must
     * not be judged by them.
     */
    public function testTheArgumentsDoNotOutliveTheirAction(): void
    {
        try {
            Application::current()->runAction('unexpected-arguments/none', ['a']);
            self::fail('The argument was not refused.');
        } catch (Exception) {
        }

        $controller = new UnexpectedArgumentsController('unexpected-arguments', Application::current());

        self::assertSame(0, $controller->runAction('none'));
    }
}

class UnexpectedArgumentsController extends Controller
{
    /**
     * @var list<string>|null
     */
    public static ?array $received = null;

    public string $option = '';

    #[Override]
    public function options($actionID): array
    {
        return [...parent::options($actionID), 'option'];
    }

    #[Override]
    public function actions(): array
    {
        return [
            'standalone' => UnexpectedArgumentsAction::class,
        ];
    }

    public function actionNone(): int
    {
        self::$received = [];
        return 0;
    }

    public function actionOne(string $first): int
    {
        self::$received = array_values(array_filter([$first, $this->option]));
        return 0;
    }

    public function actionMany(string ...$arguments): int
    {
        self::$received = array_values($arguments);
        return 0;
    }
}

/**
 * @extends Action<UnexpectedArgumentsController>
 */
class UnexpectedArgumentsAction extends Action
{
    public function run(string $first): int
    {
        UnexpectedArgumentsController::$received = [$first];
        return 0;
    }
}
