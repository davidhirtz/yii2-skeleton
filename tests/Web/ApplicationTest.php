<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Application;
use Yii;

class ApplicationTest extends TestCase
{
    /**
     * Yii's default is the lowercase `app\controllers`, which resolves against nothing in a v3 project, and the
     * path is pinned rather than derived from the namespace — deriving it needs an alias mirroring `App\`.
     */
    public function testTheControllerNamespaceIsTheProjectsOwn(): void
    {
        $app = Application::current();

        self::assertSame('App\Controllers', $app->controllerNamespace);
        self::assertSame(Yii::getAlias('@app/Controllers'), $app->getControllerPath());
    }
}
