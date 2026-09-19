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

    /**
     * A host-only `_session` beside the domain-scoped one is what PHP hands the application, so every request
     * starts a fresh session and nothing it carries survives the redirect that was meant to show it (#195).
     */
    public function testTheHostOnlyTwinOfTheSessionCookieIsRemoved(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_session=stale; _session=live';

        self::assertSame(
            ['_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax'],
            $this->send(),
        );
    }

    public function testTheHostOnlyTwinOfTheCsrfCookieIsRemoved(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_csrf=stale; _csrf=live';

        self::assertSame(
            ['_csrf=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax'],
            $this->send(),
        );
    }

    public function testACookieSentOnceIsLeftAlone(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_session=live; _csrf=live; _auth=live';

        self::assertSame([], $this->send());
    }

    /**
     * Without a `Domain` there is no twin to tell apart, and the deletion would take the live cookie with it.
     */
    public function testNothingIsRemovedWithoutACookieDomain(): void
    {
        Yii::$container->set(Cookie::class, ['sameSite' => Cookie::SAME_SITE_LAX]);
        $_SERVER['HTTP_COOKIE'] = '_session=stale; _session=live';

        self::assertSame([], $this->send());
    }

    /**
     * The name is read up to its `=`, so a cookie whose name merely ends in another's is not a duplicate.
     */
    public function testANameEndingInAnothersIsNotADuplicate(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_session=live; app_session=other';

        self::assertSame([], $this->send());
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

    private function setCookieDomain(): void
    {
        Yii::$container->set(Cookie::class, [
            'domain' => '.domain.localhost',
            'sameSite' => Cookie::SAME_SITE_LAX,
            'secure' => true,
        ]);
    }

    /**
     * @return list<string>
     */
    private function send(): array
    {
        $response = Yii::createObject(RecordingResponse::class);

        ob_start();
        $response->send();
        ob_end_clean();

        return $response->sent;
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
