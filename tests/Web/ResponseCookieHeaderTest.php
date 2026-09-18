<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Response;
use Override;
use Yii;
use yii\web\Cookie;

class ResponseCookieHeaderTest extends TestCase
{
    /**
     * `yii\web\Response::sendHeaders()` sends the first value of every header name with PHP's `$replace`, which for
     * `Set-Cookie` discards the session cookie `session_regenerate_id()` has already queued — so a login kept the
     * old session id and every flash behind the redirect died with it.
     */
    public function testACookieHeaderIsAppendedRatherThanSentThroughTheCollection(): void
    {
        $response = Yii::createObject(RecordingResponse::class);
        $response->getHeaders()
            ->add('Set-Cookie', '_auth=; Max-Age=0; Path=/')
            ->add('Set-Cookie', '_other=; Max-Age=0; Path=/');

        ob_start();
        $response->send();
        ob_end_clean();

        self::assertSame([
            '_auth=; Max-Age=0; Path=/',
            '_other=; Max-Age=0; Path=/',
        ], $response->sent);

        self::assertNull($response->getHeaders()->get('Set-Cookie'));
    }

    public function testACookieOfTheCollectionIsLeftToYii(): void
    {
        $response = Yii::createObject(RecordingResponse::class);
        $response->getCookies()->add(Yii::createObject([
            'class' => Cookie::class,
            'name' => '_auth',
            'value' => 'test',
        ]));

        ob_start();
        $response->send();
        ob_end_clean();

        self::assertSame([], $response->sent);
        self::assertNotNull($response->getCookies()->get('_auth'));
    }
}

class RecordingResponse extends Response
{
    /**
     * @var list<string>
     */
    public array $sent = [];

    #[Override]
    protected function sendCookieHeader(string $cookie): void
    {
        $this->sent[] = $cookie;
    }
}
