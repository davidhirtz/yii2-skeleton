<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;

final class SessionReadTest extends TestCase
{
    use FunctionalTestTrait;

    public function testReadingStartsNoSession(): void
    {
        $session = $this->getWebSession();

        self::assertSame('default', $session->get('key', 'default'));
        self::assertFalse($session->has('key'));
        self::assertSame([], $session->getAllFlashes());
        self::assertFalse($session->getIsActive());
    }

    public function testWritingStartsOne(): void
    {
        $session = $this->getWebSession();
        $session->set('key', 'value');

        self::assertTrue($session->getIsActive());
        self::assertTrue($session->has('key'));
        self::assertSame('value', $session->get('key'));
    }

    public function testAFlashStartsOne(): void
    {
        $session = $this->getWebSession();
        $session->addFlash('success', 'Saved');

        self::assertTrue($session->getIsActive());
        self::assertSame(['success' => ['Saved']], $session->getAllFlashes());
    }

    public function testACookielessVisitorOfTheLoginPageGetsNoSession(): void
    {
        $count = $this->countQueries(fn () => $this->open('admin/account/login'), '/`session`/');

        self::assertResponseIsSuccessful();
        self::assertSame(0, $count);
        self::assertFalse($this->getWebSession()->getIsActive());
    }
}
