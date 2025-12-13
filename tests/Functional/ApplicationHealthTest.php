<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Yii;

class ApplicationHealthTest extends TestCase
{
    use FunctionalTestTrait;

    public function testApplicationHealth(): void
    {
        $this->open('application-health');
        self::assertResponseIsSuccessful();
    }
}
