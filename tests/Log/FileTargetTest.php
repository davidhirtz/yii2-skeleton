<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Log;

use DateTime;
use DateTimeZone;
use Hirtz\Skeleton\Log\FileTarget;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\log\Logger;

class FileTargetTest extends TestCase
{
    /**
     * `maskVars` replaces a whole variable, which is no use for a token that sits inside the one variable saying
     * which page failed (monorepo issue #166).
     */
    public function testTheTokenIsMaskedWhereverTheUrlAppears(): void
    {
        $_GET = ['code' => 'a-real-token', 'q' => 'search'];
        $_SERVER['REQUEST_URI'] = '/admin/account/reset?code=a-real-token&q=search';
        $_SERVER['QUERY_STRING'] = 'code=a-real-token&q=search';
        $_SERVER['HTTP_REFERER'] = 'https://www.domain.localhost/admin/account/confirm?code=a-real-token';

        $context = $this->getContextMessage();

        self::assertStringNotContainsString('a-real-token', $context);

        // The page an error happened on is most of what makes the entry readable, so only the value goes.
        self::assertStringContainsString('/admin/account/reset?code=***&q=search', $context);
        self::assertStringContainsString('code=***&q=search', $context);
        self::assertStringContainsString('/admin/account/confirm?code=***', $context);

        // `maskVars` still answers for the parameter as a variable of its own, which reads `'code' => '***'`.
        self::assertStringContainsString("'code' => '***'", $context);
        self::assertStringContainsString("'q' => 'search'", $context);
    }

    /**
     * The name is matched whole: a parameter that merely ends in one of them is not a credential.
     */
    public function testAnUnnamedParameterIsLeftAlone(): void
    {
        $_SERVER['REQUEST_URI'] = '/admin/media/file/index?q=codex&passcode=kept&folder=2';

        self::assertStringContainsString('?q=codex&passcode=kept&folder=2', $this->getContextMessage());
    }

    /**
     * Yii stamps the line with `date()`, which answers in the process time zone — and `Models\User::findIdentity()`
     * pins that to the account behind the request, so the file held one zone per user and was not even in
     * chronological order, while the admin reads it back as UTC (monorepo issue #192).
     */
    public function testTheTimestampIsWrittenInUtc(): void
    {
        $timestamp = 1600000000;
        $timeZone = Yii::$app->getTimeZone();

        $utc = (new DateTime("@$timestamp"))->format('Y-m-d H:i:s');
        $local = (new DateTime("@$timestamp"))
            ->setTimezone(new DateTimeZone('America/New_York'))
            ->format('Y-m-d H:i:s');

        Yii::$app->setTimeZone('America/New_York');

        try {
            $message = (new FileTarget())->formatMessage(['a message', Logger::LEVEL_ERROR, 'application', $timestamp]);
        } finally {
            Yii::$app->setTimeZone($timeZone);
        }

        self::assertSame($utc, substr($message, 0, 19));
        self::assertStringNotContainsString($local, $message);
    }

    private function getContextMessage(): string
    {
        $target = new TestFileTarget([
            'logVars' => ['_GET', '_SERVER'],
            'maskVars' => ['_GET.code'],
        ]);

        return $target->getContextMessage();
    }
}

/**
 * Declared here rather than beside the test: a `path` repository installs no dev autoload, so a class in another
 * test file is only loaded when that file runs.
 */
class TestFileTarget extends FileTarget
{
    #[Override]
    public function getContextMessage(): string
    {
        return parent::getContextMessage();
    }
}
