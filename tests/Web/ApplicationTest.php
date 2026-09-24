<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\Application;
use ReflectionClass;
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

    /**
     * The built-in server names the requested file, `web/images/logo.svg`, whenever it exists; rooting the
     * application two levels above it would write its runtime into the webroot.
     *
     * @see https://github.com/davidhirtz/yii2-monorepo/issues/264
     */
    public function testTheEntryScriptIsNeverTheRequestedFile(): void
    {
        $app = (new ReflectionClass(EntryScriptApplication::class))->newInstanceWithoutConstructor();
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? null;

        try {
            $_SERVER['SCRIPT_FILENAME'] = '/srv/site/web/index.php';
            self::assertSame('/srv/site/web/index.php', $app->getEntryScriptForTest());

            $_SERVER['SCRIPT_FILENAME'] = '/srv/site/web/images/logo.svg';
            self::assertSame(get_included_files()[0], $app->getEntryScriptForTest());
        } finally {
            $_SERVER['SCRIPT_FILENAME'] = $scriptFilename;
        }
    }
}

/**
 * @extends Application<User>
 */
class EntryScriptApplication extends Application
{
    public function getEntryScriptForTest(): string
    {
        return $this->getEntryScript();
    }
}
