<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Log;

use Closure;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Log\SentryTarget;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use RuntimeException;
use Sentry\Event;
use Sentry\Integration\ErrorListenerIntegration;
use Sentry\Integration\ExceptionListenerIntegration;
use Sentry\Integration\FatalErrorListenerIntegration;
use Sentry\Integration\ModulesIntegration;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Yii;
use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * The transport is replaced, so nothing here reaches the network. Sentry's hub is a process-wide singleton the
 * target sets, so each test starts from a fresh one.
 */
class SentryTargetTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function tearDown(): void
    {
        SentrySdk::init();
        parent::tearDown();
    }

    public function testATargetWithoutADsnIsRefused(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('sentryDsn');

        Yii::createObject(SentryTarget::class);
    }

    public function testAnExceptionIsReportedWithItsStackTrace(): void
    {
        $transport = $this->export([[new RuntimeException('Something broke'), Logger::LEVEL_ERROR, 'application', 0.0, [], 0]]);

        $event = $transport->events[0] ?? null;
        self::assertNotNull($event);

        $exceptions = $event->getExceptions();
        self::assertCount(1, $exceptions);
        self::assertSame(RuntimeException::class, $exceptions[0]->getType());
        self::assertSame('Something broke', $exceptions[0]->getValue());
        self::assertNotNull($exceptions[0]->getStacktrace());
    }

    /**
     * `captureException()` names no level of its own, and Sentry defaults a level-less event to `error` — so
     * without the scope's, every exception would arrive as one whatever Yii logged it as.
     */
    public function testAnExceptionKeepsTheLevelItWasLoggedAt(): void
    {
        $transport = $this->export([[new RuntimeException('Odd but survivable'), Logger::LEVEL_WARNING, 'application', 0.0, [], 0]]);

        self::assertEquals(Severity::warning(), $transport->events[0]->getLevel());
    }

    public function testAMessageIsReportedWithItsLevelAndCategory(): void
    {
        $transport = $this->export([['Something is off', Logger::LEVEL_WARNING, 'app\\Widget', 0.0, [], 0]]);

        $event = $transport->events[0] ?? null;
        self::assertNotNull($event);

        self::assertSame('Something is off', $event->getMessage());
        self::assertEquals(Severity::warning(), $event->getLevel());
        self::assertSame('app\\Widget', $event->getTags()['category'] ?? null);
    }

    /**
     * A message that is not a string — `Yii::error()` takes anything — still has to arrive as something readable.
     */
    public function testAnArrayMessageIsExported(): void
    {
        $transport = $this->export([[['key' => 'value'], Logger::LEVEL_ERROR, 'application', 0.0, [], 0]]);

        self::assertStringContainsString("'key' => 'value'", (string)$transport->events[0]->getMessage());
    }

    public function testTheReportNamesTheUserByIdAlone(): void
    {
        $user = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($user);

        $transport = $this->export([['Something broke', Logger::LEVEL_ERROR, 'application', 0.0, [], 0]]);

        $reported = $transport->events[0]->getUser();
        self::assertNotNull($reported);
        self::assertSame($user->id, $reported->getId());
        self::assertNull($reported->getEmail());
        self::assertNull($reported->getIpAddress());
    }

    public function testAGuestIsReportedWithoutAUser(): void
    {
        $transport = $this->export([['Something broke', Logger::LEVEL_ERROR, 'application', 0.0, [], 0]]);
        self::assertNull($transport->events[0]->getUser());
    }

    /**
     * The frames of a report point into the same twelve bundle repositories whichever installation raised it, so
     * the root package Composer recorded is what tells two deployments apart.
     */
    public function testEveryReportNamesTheProject(): void
    {
        $transport = $this->export([['Something broke', Logger::LEVEL_ERROR, 'application', 0.0, [], 0]]);
        $tags = $transport->events[0]->getTags();

        self::assertSame(VersionHelper::getApplicationName(), $tags['project'] ?? null);
        self::assertSame(VersionHelper::getApplicationVersion(), $tags['project_version'] ?? null);
    }

    /**
     * The tags are the one client option merged rather than replaced.
     */
    public function testAProjectKeepsTheProjectTagBesideItsOwn(): void
    {
        $transport = new TestTransport();

        $target = Yii::createObject([
            'class' => SentryTarget::class,
            'dsn' => 'https://public@sentry.localhost/1',
            'clientOptions' => ['transport' => $transport, 'tags' => ['tenant' => 'acme']],
        ]);

        self::assertInstanceOf(SentryTarget::class, $target);

        $target->messages = [['Something broke', Logger::LEVEL_ERROR, 'application', 0.0, [], 0]];
        $target->export();

        $tags = $transport->events[0]->getTags();

        self::assertSame('acme', $tags['tenant'] ?? null);
        self::assertSame(VersionHelper::getApplicationName(), $tags['project'] ?? null);
    }

    /**
     * The posted body is what the file log's `maskVars` keeps out of it, and no mask of ours reaches Sentry's own
     * request integration — so the integration is told not to read one.
     */
    public function testThePostedBodyAndTheCookiesAreNeverSent(): void
    {
        $options = $this->getClientOptions();

        self::assertSame('none', $options['max_request_body_size']);
        self::assertFalse($options['send_default_pii']);
    }

    /**
     * Yii's error handler reports through this target. Sentry's own listeners would report a second time and take
     * over the handler that renders the error page.
     */
    public function testSentryInstallsNoErrorHandlerOfItsOwn(): void
    {
        $filter = $this->getClientOptions()['integrations'];
        self::assertIsCallable($filter);

        $integrations = $filter([
            new ExceptionListenerIntegration(),
            new ErrorListenerIntegration(),
            new FatalErrorListenerIntegration(),
            new ModulesIntegration(),
        ]);

        self::assertCount(1, $integrations);
        self::assertInstanceOf(ModulesIntegration::class, $integrations[0]);
    }

    public function testTheReportIsGroupedByEnvironmentAndCommit(): void
    {
        $options = $this->getClientOptions();

        self::assertSame(YII_ENV, $options['environment']);

        // Sentry's own `package@version`, so the Releases view reads as the project rather than as a bare commit.
        self::assertSame(
            VersionHelper::getApplicationName() . '@' . VersionHelper::getApplicationReference(),
            $options['release'],
        );
    }

    /**
     * A deploy pipeline sets these, and it is the only thing that can associate commits with a release — so a
     * default of ours must not shadow Sentry's own resolution of them.
     */
    public function testTheDeploymentsOwnReleaseAndEnvironmentWin(): void
    {
        $_SERVER['SENTRY_RELEASE'] = 'acme/website@0123456789abcdef0123456789abcdef01234567';
        $_SERVER['SENTRY_ENVIRONMENT'] = 'staging';

        $options = $this->getClientOptions();

        self::assertSame('acme/website@0123456789abcdef0123456789abcdef01234567', $options['release']);
        self::assertSame('staging', $options['environment']);
    }

    /**
     * The property is above the variable, so a project pinning either in its own configuration still can.
     */
    public function testTheConfiguredReleaseAndEnvironmentWinOverBoth(): void
    {
        $_SERVER['SENTRY_RELEASE'] = 'acme/website@deadbeef';
        $_SERVER['SENTRY_ENVIRONMENT'] = 'staging';

        $target = new TestSentryTarget([
            'dsn' => 'https://public@sentry.localhost/1',
            'release' => 'pinned',
            'environment' => 'production',
        ]);

        self::assertSame('pinned', $target->getClientOptions()['release']);
        self::assertSame('production', $target->getClientOptions()['environment']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getClientOptions(): array
    {
        return (new TestSentryTarget(['dsn' => 'https://public@sentry.localhost/1']))->getClientOptions();
    }

    /**
     * `Target::collect()` appends the `logVars` dump as a message of its own — the cookies and the posted body
     * among them, masked for a file on this server rather than for an external service.
     */
    public function testTheContextDumpIsNotReported(): void
    {
        $transport = new TestTransport();
        $target = $this->createTarget($transport);

        $target->collect([['Something broke', Logger::LEVEL_ERROR, 'application', 0.0, [], 0]], true);

        self::assertCount(1, $transport->events);
        self::assertSame('Something broke', $transport->events[0]->getMessage());
    }

    /**
     * `send_default_pii` gates the cookies and the headers, not the URL — a confirmation or reset link carries
     * its token in the query string, and Sentry's request integration sends both either way (issue #166).
     *
     * The callback is exercised rather than the integration: `RequestIntegration::setupOnce()` registers a
     * *global* event processor bound to whichever instance ran first in the process, so a fetcher of one's own
     * reaches nothing once any other client has been built.
     */
    public function testTheTokenIsMaskedInTheRequestUrl(): void
    {
        $request = $this->beforeSend()->getRequest();

        self::assertSame('http://www.test.localhost/admin/account/reset?code=***&q=search', $request['url']);
        self::assertSame('code=***&q=search', $request['query_string']);
        self::assertSame('GET', $request['method']);
    }

    /**
     * A project's own callback is composed with the masking rather than replacing it, and sees the masked event.
     */
    public function testAProjectsOwnBeforeSendRunsOnTheMaskedEvent(): void
    {
        $seen = null;

        $event = $this->beforeSend([
            'before_send' => function (Event $event) use (&$seen): Event {
                $seen = $event->getRequest()['url'] ?? null;
                return $event;
            },
        ]);

        self::assertSame('http://www.test.localhost/admin/account/reset?code=***&q=search', $seen);
        self::assertSame($seen, $event->getRequest()['url']);
    }

    /**
     * @param array<string, mixed> $clientOptions
     */
    private function beforeSend(array $clientOptions = []): Event
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'http://www.test.localhost/admin/account/reset?code=a-real-token&q=search',
            'method' => 'GET',
            'query_string' => 'code=a-real-token&q=search',
        ]);

        $target = new TestSentryTarget([
            'dsn' => 'https://public@sentry.localhost/1',
            'clientOptions' => $clientOptions,
        ]);

        $masked = ($target->getBeforeSend())($event);

        self::assertInstanceOf(Event::class, $masked);
        return $masked;
    }

    /**
     * @param list<array{0: mixed, 1: int, 2: string, 3: float, 4: array<mixed>, 5: int}> $messages
     */
    private function export(array $messages): TestTransport&TransportInterface
    {
        $transport = new TestTransport();
        $target = $this->createTarget($transport);

        $target->messages = $messages;
        $target->export();

        return $transport;
    }

    private function createTarget(TestTransport $transport): SentryTarget
    {
        $target = Yii::createObject([
            'class' => SentryTarget::class,
            'dsn' => 'https://public@sentry.localhost/1',
            'clientOptions' => ['transport' => $transport],
        ]);

        self::assertInstanceOf(SentryTarget::class, $target);
        return $target;
    }
}

class TestSentryTarget extends SentryTarget
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getClientOptions(): array
    {
        return parent::getClientOptions();
    }

    #[Override]
    public function getBeforeSend(): Closure
    {
        return parent::getBeforeSend();
    }
}

/**
 * Declared here rather than beside the test: a `path` repository installs no dev autoload, so a class in another
 * test file is only loaded when that file runs.
 */
class TestTransport implements TransportInterface
{
    /** @var list<Event> */
    public array $events = [];

    public function send(Event $event): Result
    {
        $this->events[] = $event;
        return new Result(ResultStatus::success(), $event);
    }

    public function close(?int $timeout = null): Result
    {
        return new Result(ResultStatus::success());
    }
}
