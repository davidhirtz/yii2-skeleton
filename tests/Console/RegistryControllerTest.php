<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hirtz\Skeleton\Console\Application;
use Hirtz\Skeleton\Console\Controllers\RegistryController;
use Hirtz\Skeleton\Helpers\VersionHelper;
use Hirtz\Skeleton\Registry\RegistryClient;
use Hirtz\Skeleton\Registry\Report;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Psr\Http\Message\RequestInterface;
use Yii;
use yii\console\ExitCode;

class RegistryControllerTest extends TestCase
{
    protected string $applicationClass = Application::class;

    /**
     * @var list<RequestInterface>
     */
    private array $requests = [];

    public function testANotConfiguredRegistryIsNotAnError(): void
    {
        $controller = $this->createController();

        self::assertSame(ExitCode::OK, $controller->actionPush());
        self::assertSame('Registry not configured: `registryUrl` is not set in params, nothing sent.' . PHP_EOL, $controller->flushStdOutBuffer());

        Yii::$app->params['registryUrl'] = 'https://registry.test.localhost/registry/push';

        self::assertSame(ExitCode::OK, $controller->actionPush());
        self::assertSame('Registry not configured: `registryKey` is not set in params, nothing sent.' . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testTheReportIsPostedWithTheBearerKey(): void
    {
        $this->configure(new Response(201, ['Content-Type' => 'application/json'], '{"installation":7,"project":"monorepo","created":true}'));
        $controller = $this->createController();

        self::assertSame(ExitCode::OK, $controller->actionPush());

        $request = $this->getRequest(0);
        $body = json_decode((string)$request->getBody(), true);

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://registry.test.localhost/registry/push', (string)$request->getUri());
        self::assertSame('Bearer secret-key', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

        self::assertIsArray($body);
        self::assertSame(Report::SCHEMA, $body['schema']);
        self::assertSame(VersionHelper::getApplicationName(), $body['name']);
        self::assertArrayHasKey('davidhirtz/yii2-skeleton', $body['extensions']);

        $output = $controller->flushStdOutBuffer();

        self::assertStringContainsString('Reported ' . VersionHelper::getApplicationName(), $output);
        self::assertStringContainsString('to registry.test.localhost as project "monorepo", installation #7.', $output);
    }

    public function testTheUrlOptionNamesTheInstallation(): void
    {
        $this->configure(new Response(200, [], '{}'));

        $controller = $this->createController();
        $controller->url = 'https://www.example.com';

        self::assertSame(ExitCode::OK, $controller->actionPush());
        self::assertSame('https://www.example.com', json_decode((string)$this->getRequest(0)->getBody(), true)['url']);
        self::assertStringContainsString('(https://www.example.com) to registry.test.localhost.', $controller->flushStdOutBuffer());
    }

    public function testARevokedKeyIsAWarningUnlessStrict(): void
    {
        $this->configure(
            new Response(401, ['Content-Type' => 'application/json'], '{"name":"Unauthorized","message":"The key was revoked.","status":401}'),
            new Response(401, ['Content-Type' => 'application/json'], '{"name":"Unauthorized","message":"The key was revoked.","status":401}'),
        );

        $controller = $this->createController();

        self::assertSame(ExitCode::OK, $controller->actionPush());
        self::assertSame('Registry push failed: Registry answered 401: The key was revoked.' . PHP_EOL, $controller->flushStdOutBuffer());

        $controller->strict = true;

        self::assertSame(ExitCode::UNAVAILABLE, $controller->actionPush());
        self::assertSame('Registry push failed: Registry answered 401: The key was revoked.' . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testAServerErrorWithoutAJsonBodyNamesTheStatus(): void
    {
        $this->configure(new Response(500, [], '<html>Internal Server Error</html>'));
        $controller = $this->createController();
        $controller->strict = true;

        self::assertSame(ExitCode::UNAVAILABLE, $controller->actionPush());
        self::assertSame('Registry push failed: Registry answered 500' . PHP_EOL, $controller->flushStdOutBuffer());
    }

    public function testAnUnreachableRegistryIsAWarningUnlessStrict(): void
    {
        $exception = new ConnectException('Connection refused', new Request('POST', 'https://registry.test.localhost/registry/push'));
        $this->configure($exception, $exception);

        $controller = $this->createController();

        self::assertSame(ExitCode::OK, $controller->actionPush());
        self::assertSame('Registry push failed: Connection refused' . PHP_EOL, $controller->flushStdOutBuffer());

        $controller->strict = true;

        self::assertSame(ExitCode::UNAVAILABLE, $controller->actionPush());
    }

    public function testShowPrintsTheReportWithoutSendingIt(): void
    {
        $this->configure();
        $controller = $this->createController();

        self::assertSame(ExitCode::OK, $controller->actionShow());

        $report = json_decode($controller->flushStdOutBuffer(), true);

        self::assertIsArray($report);
        self::assertSame(Report::SCHEMA, $report['schema']);
        self::assertSame(VersionHelper::getApplicationName(), $report['name']);
        self::assertSame([], $this->requests);
    }

    public function testTheOptionsAreDeclared(): void
    {
        $controller = $this->createController();

        self::assertContains('url', $controller->options('push'));
        self::assertContains('strict', $controller->options('push'));
        self::assertContains('url', $controller->options('show'));
        self::assertNotContains('strict', $controller->options('show'));
    }

    private function configure(Response|ConnectException ...$responses): void
    {
        Yii::$app->params['registryUrl'] = 'https://registry.test.localhost/registry/push';
        Yii::$app->params['registryKey'] = 'secret-key';

        $stack = HandlerStack::create(new MockHandler(array_values($responses)));

        // Guzzle's own history middleware writes into a container typed `array|ArrayAccess`, which no property
        // type satisfies; recording the requests directly is two lines.
        $stack->push(fn (callable $handler): callable => function (RequestInterface $request, array $options) use ($handler): mixed {
            $this->requests[] = $request;
            return $handler($request, $options);
        });

        Yii::$container->set(RegistryClient::class, [
            'client' => new Client(['handler' => $stack]),
        ]);
    }

    private function getRequest(int $index): RequestInterface
    {
        self::assertArrayHasKey($index, $this->requests);
        return $this->requests[$index];
    }

    private function createController(): RegistryControllerMock
    {
        return new RegistryControllerMock('registry', Yii::$app);
    }
}

class RegistryControllerMock extends RegistryController
{
    use StdOutBufferControllerTrait;
}
