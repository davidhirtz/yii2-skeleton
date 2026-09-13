<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\RedirectController;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Yii;

class RedirectControllerTest extends TestCase
{
    public function testASelfRedirectIsDeleted(): void
    {
        $this->insertBrokenRedirect('first', 'first');

        $this->createController()->actionClean();

        self::assertSame(0, (int)Redirect::find()->count());
    }

    /**
     * The shape the cms writes: the request side names the tenant's host, the target is relative to it, so the
     * two columns never compare equal as strings and only the host list makes the loop visible.
     */
    public function testAHostQualifiedSelfRedirectIsDeleted(): void
    {
        $this->insertRedirect('www.example.com/first', 'first');

        $this->createController()->actionClean();

        self::assertSame(0, (int)Redirect::find()->count());
    }

    public function testAHostQualifiedSelfRedirectIsKeptWhenTheHostIsUnknown(): void
    {
        $this->insertRedirect('www.example.com/first', 'first');

        $controller = $this->createController('other.example.com');
        $controller->actionClean();

        self::assertSame(1, (int)Redirect::find()->count());
        self::assertStringContainsString('Nothing to clean up', $controller->flushStdOutBuffer());
    }

    public function testBothMembersOfACycleAreDeleted(): void
    {
        $this->insertRedirect('one', 'two');
        $this->insertBrokenRedirect('two', 'one');

        $this->createController()->actionClean();

        self::assertSame(0, (int)Redirect::find()->count());
    }

    /**
     * A redirect into a cycle is broken too, but it is not the broken part: deleting it would throw away a
     * request URI that works again the moment the cycle is gone.
     */
    public function testARedirectLeadingIntoACycleIsReportedAndKept(): void
    {
        $this->insertRedirect('one', 'two');
        $this->insertBrokenRedirect('two', 'one');
        $this->insertBrokenRedirect('zero', 'one');

        $controller = $this->createController();
        $controller->actionClean();

        self::assertStringContainsString('zero -> one leads into a cycle', $controller->flushStdOutBuffer());
        self::assertSame(['zero'], $this->getRequestUris());
    }

    public function testAChainIsShortenedToItsEnd(): void
    {
        $this->insertRedirect('first', 'second');
        $this->insertRedirect('second', 'third');

        $this->createController()->actionClean();

        self::assertSame('third', Redirect::findOne(['request_uri' => 'first'])->url);
        self::assertSame('third', Redirect::findOne(['request_uri' => 'second'])->url);
    }

    public function testAHealthyRedirectIsLeftAlone(): void
    {
        $this->insertRedirect('old', 'new');

        $controller = $this->createController();
        $controller->actionClean();

        self::assertStringContainsString('Nothing to clean up', $controller->flushStdOutBuffer());
        self::assertSame('new', Redirect::findOne(['request_uri' => 'old'])->url);
    }

    public function testADryRunWritesNothing(): void
    {
        $this->insertBrokenRedirect('first', 'first');
        $this->insertRedirect('old', 'mid');
        $this->insertRedirect('mid', 'new');

        $controller = $this->createController();
        $controller->dryRun = true;
        $controller->actionClean();

        $output = $controller->flushStdOutBuffer();

        self::assertStringContainsString('Would delete 1, would shorten 1.', $output);
        self::assertSame(['first', 'mid', 'old'], $this->getRequestUris());
        self::assertSame('mid', Redirect::findOne(['request_uri' => 'old'])->url);
    }

    public function testAnEmptyTableIsReportedRatherThanConfirmed(): void
    {
        $controller = $this->createController();
        $controller->actionClean();

        self::assertStringContainsString('No redirects found', $controller->flushStdOutBuffer());
    }

    /**
     * @return list<string>
     */
    private function getRequestUris(): array
    {
        $requestUris = Redirect::find()
            ->select(['request_uri'])
            ->orderBy(['request_uri' => SORT_ASC])
            ->column();

        return array_values($requestUris);
    }

    private function insertRedirect(string $requestUri, string $url): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = $requestUri;
        $redirect->url = $url;

        self::assertTrue($redirect->insert(), implode(' ', $redirect->getErrorSummary(true)));

        return $redirect;
    }

    /**
     * A row the validator would refuse today, as every one of these was written before it did.
     */
    private function insertBrokenRedirect(string $requestUri, string $url): Redirect
    {
        $redirect = $this->insertRedirect($requestUri, "$requestUri-placeholder");
        $redirect->updateAttributes(['url' => $url]);

        return $redirect;
    }

    private function createController(string $hosts = 'www.example.com'): RedirectControllerMock
    {
        $controller = new RedirectControllerMock('redirect', Yii::$app);
        $controller->interactive = false;
        $controller->hosts = $hosts;

        return $controller;
    }
}

class RedirectControllerMock extends RedirectController
{
    use StdOutBufferControllerTrait;
}
