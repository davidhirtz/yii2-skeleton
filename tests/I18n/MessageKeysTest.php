<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\MessageKeysTrait;

class MessageKeysTest extends TestCase
{
    use MessageKeysTrait;

    public function testTheSkeletonKeysFollowTheScheme(): void
    {
        self::assertMessageKeys(dirname(__DIR__, 2) . '/messages', 'skeleton');
    }
}
