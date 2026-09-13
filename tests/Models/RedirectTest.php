<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;

class RedirectTest extends TestCase
{
    public function testATargetEqualToTheRequestUriIsRefused(): void
    {
        $redirect = $this->createRedirect('about', 'about');

        self::assertFalse($redirect->insert());
        self::assertSame(['url'], array_keys($redirect->getErrors()));
    }

    public function testATargetPointingAtAnotherRedirectIsFlattened(): void
    {
        $this->insertRedirect('old', 'current');

        $redirect = $this->insertRedirect('older', 'old');

        self::assertSame('current', $redirect->url);
    }

    public function testAChainIsFollowedToItsEnd(): void
    {
        $this->insertRedirect('second', 'third');
        $this->insertRedirect('first', 'second');

        $redirect = $this->insertRedirect('zeroth', 'first');

        self::assertSame('third', $redirect->url);
    }

    /**
     * The rename-back cycle: `about` was renamed to `about-test`, and renaming it back resolves the new target
     * through the redirect the first rename left behind, landing on the new redirect's own request URI.
     */
    public function testARenameBackIsRefusedInsteadOfBeingFlattenedIntoASelfRedirect(): void
    {
        $this->insertRedirect('about', 'about-test');

        $redirect = $this->createRedirect('about-test', 'about');

        self::assertFalse($redirect->insert());
        self::assertSame(['url'], array_keys($redirect->getErrors()));
        self::assertFalse(Redirect::find()->where(['request_uri' => 'about-test'])->exists());
    }

    /**
     * A cycle among the rows already in the table — a legacy row written before the validator caught them — must
     * not walk forever.
     */
    public function testAnExistingCycleTerminatesTheChainLookup(): void
    {
        $this->insertRedirect('one', 'two');
        $second = $this->insertRedirect('two', 'three');

        // `updateAttributes()` skips validation, which is the only way such a row exists
        $second->updateAttributes(['url' => 'one']);

        $redirect = $this->insertRedirect('zero', 'one');

        self::assertSame('one', $redirect->url);
    }

    private function insertRedirect(string $requestUri, string $url): Redirect
    {
        $redirect = $this->createRedirect($requestUri, $url);

        self::assertTrue($redirect->insert(), implode(' ', $redirect->getErrorSummary(true)));

        return $redirect;
    }

    private function createRedirect(string $requestUri, string $url): Redirect
    {
        $redirect = Redirect::create();
        $redirect->request_uri = $requestUri;
        $redirect->url = $url;

        return $redirect;
    }
}
