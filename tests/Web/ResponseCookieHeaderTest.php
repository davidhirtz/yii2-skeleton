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
     * A stale `_session` beside the domain-scoped one is what PHP hands the application, so every request starts a
     * fresh session and nothing it carries survives the redirect that was meant to show it (#195).
     */
    public function testTheTwinsOfTheSessionCookieAreRemoved(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_session=stale; _session=live';

        self::assertSame([
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax',
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax; Domain=www.test.localhost',
        ], $this->send());
    }

    /**
     * A tenant scopes the cookies to its host, so a parent domain configured later leaves a twin that a deletion
     * without `Domain` never reaches: the upload chunks of monorepo issue #555 each landed in a new session.
     */
    public function testTheTwinOfATenantScopedSessionCookieIsRemoved(): void
    {
        $this->setCookieDomain('www.test.localhost');
        $_SERVER['HTTP_COOKIE'] = '_session=stale; _session=live';

        self::assertSame([
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax',
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax; Domain=test.localhost',
        ], $this->send());
    }

    public function testTheTwinsOfTheCsrfCookieAreRemoved(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_csrf=stale; _csrf=live';

        self::assertSame([
            '_csrf=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax',
            '_csrf=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax; Domain=www.test.localhost',
        ], $this->send());
    }

    public function testACookieSentOnceIsLeftAlone(): void
    {
        $this->setCookieDomain();
        $_SERVER['HTTP_COOKIE'] = '_session=live; _csrf=live; _auth=live';

        self::assertSame([], $this->send());
    }

    /**
     * Without a `Domain` the live cookie is the host-only one, which only a deletion without `Domain` would reach.
     */
    public function testTheHostOnlyCookieIsKeptWithoutACookieDomain(): void
    {
        Yii::$container->set(Cookie::class, ['sameSite' => Cookie::SAME_SITE_LAX]);
        $_SERVER['HTTP_COOKIE'] = '_session=stale; _session=live';

        self::assertSame([
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax; Domain=www.test.localhost',
            '_session=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; Secure; HttpOnly; SameSite=Lax; Domain=test.localhost',
        ], $this->send());
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

    private function setCookieDomain(string $domain = '.test.localhost'): void
    {
        Yii::$container->set(Cookie::class, [
            'domain' => $domain,
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
