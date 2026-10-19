<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\ScriptErrorController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use yii\log\Logger;

class ScriptErrorTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const string URL = 'https://www.test.localhost/admin/script-error/create';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger->isRecording = true;
    }

    public function testTheLayoutNamesTheEndpointForAUser(): void
    {
        $this->open('admin/account/login');
        self::assertSelectorNotExists('body[data-script-error-url]');

        $this->login();
        $this->open('admin/dashboard/index');

        self::assertSelectorExists('body[data-script-error-url="/admin/script-error/create"][data-csrf-param]');
    }

    public function testAnErrorIsLogged(): void
    {
        $this->login();
        $this->open('admin/dashboard/index');

        $this->post([
            $this->getCsrfParam() => $this->getCsrfToken(),
            'message' => "Cannot read properties of null (reading 'getBoundingClientRect') Å ą х 漢 🙂",
            'file' => 'https://www.test.localhost/assets/admin.js',
            'line' => '12',
            'column' => '34',
            'stack' => 'TypeError: at dropdown (admin.js:12:34)',
            'url' => 'https://www.test.localhost/admin/entry/index',
            'userAgent' => 'Mozilla/5.0',
            'ignored' => ['array'],
        ]);

        self::assertResponseStatusCodeSame(204);

        $message = $this->getLastMessage();

        self::assertSame(Logger::LEVEL_ERROR, $message[1]);
        self::assertStringStartsWith("Cannot read properties of null (reading 'getBoundingClientRect') Å ą х 漢 🙂\n", $message[0]);
        self::assertStringContainsString('at https://www.test.localhost/assets/admin.js:12:34', $message[0]);
        self::assertStringContainsString('URL: https://www.test.localhost/admin/entry/index', $message[0]);
        self::assertStringContainsString('User agent: Mozilla/5.0', $message[0]);
        self::assertStringEndsWith('TypeError: at dropdown (admin.js:12:34)', $message[0]);
    }

    public function testALongStackIsCut(): void
    {
        $this->login();
        $this->open('admin/dashboard/index');

        $this->post([
            $this->getCsrfParam() => $this->getCsrfToken(),
            'message' => 'Error',
            'stack' => str_repeat('🙂', 10000),
        ]);

        self::assertResponseStatusCodeSame(204);
        self::assertStringEndsWith("\n" . str_repeat('🙂', 8000), $this->getLastMessage()[0]);
    }

    public function testAnErrorWithoutAMessageIsRefused(): void
    {
        $this->login();
        $this->open('admin/dashboard/index');

        $this->post([$this->getCsrfParam() => $this->getCsrfToken()]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testAnErrorWithoutTheCsrfTokenIsRefused(): void
    {
        $this->login();
        $this->open('admin/dashboard/index');

        $this->post(['message' => 'Error']);

        self::assertResponseStatusCodeSame(400);
        self::assertSame([], $this->getMessages());
    }

    public function testAGuestCannotReport(): void
    {
        $this->open('admin/account/login');

        self::$client->followRedirects(false);
        $this->post([$this->getCsrfParam() => $this->getCsrfToken(), 'message' => 'Error']);

        self::assertResponseStatusCodeSame(302);
        self::assertSame([], $this->getMessages());
    }

    public function testAGetIsRefused(): void
    {
        $this->login();
        $this->open('admin/script-error/create');

        self::assertResponseStatusCodeSame(405);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function post(array $values): void
    {
        self::$crawler = self::$client->request('POST', self::URL, $values);
    }

    /**
     * What `includes/scriptError.ts` reads off the page it runs in.
     */
    private function getCsrfToken(): string
    {
        $headers = json_decode((string)self::$crawler->filter('#wrap')->attr('hx-headers:inherited'), true);
        return is_array($headers) ? (string)end($headers) : '';
    }

    private function getCsrfParam(): string
    {
        return $this->getWebRequest()->csrfParam;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getLastMessage(): array
    {
        $messages = $this->getMessages();
        return $messages[array_key_last($messages)] ?? self::fail('No script error was logged.');
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    private function getMessages(): array
    {
        return array_values(array_filter(
            $this->logger->messages,
            fn (array $message): bool => $message[2] === ScriptErrorController::LOG_CATEGORY,
        ));
    }

    private function login(): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }
}
