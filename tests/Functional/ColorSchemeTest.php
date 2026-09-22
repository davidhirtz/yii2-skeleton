<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class ColorSchemeTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    /**
     * No attribute is the third state: nothing pinned, so `prefers-color-scheme` decides in the stylesheet. The
     * server cannot answer it — `Sec-CH-Prefers-Color-Scheme` is Chromium-only and needs an `Accept-CH` first.
     * A guest has no column either, so the login page never carries one.
     */
    public function testTheLoginPageRendersNoAttribute(): void
    {
        $this->open('admin/account/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('html[data-theme]');
    }

    public function testTheAccountColumnIsRenderedIntoTheLayout(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->color_scheme = User::COLOR_SCHEME_DARK;
        $user->update();

        $this->login('owner');
        $this->open('admin/dashboard/index');

        self::assertSelectorExists('html[data-theme="dark"]');
    }

    /**
     * `data-theme` is on `<html>`, which every swap leaves standing — so a scheme the account form changed only
     * reaches the document on a full load, and the save has to ask for one.
     */
    public function testTheAccountFormRefreshesTheDocumentWhenTheSchemeChanged(): void
    {
        $this->login('owner');
        $this->open('admin/account/update');

        $this->submit(
            values: $this->prefixFormValues($this->getUserFromFixture('owner'), [
                'color_scheme' => User::COLOR_SCHEME_DARK,
            ]),
            server: ['HTTP_HX_REQUEST' => 'true'],
        );

        self::assertResponseHeaderSame('hx-refresh', 'true');
    }

    /**
     * A save that left the scheme where it was answers the ordinary refresh, or every account save would reload
     * the whole document.
     */
    public function testTheAccountFormDoesNotRefreshWhenTheSchemeIsUnchanged(): void
    {
        $this->login('owner');
        $this->open('admin/account/update');

        $this->submit(
            values: $this->prefixFormValues($this->getUserFromFixture('owner'), [
                'name' => 'updated',
            ]),
            server: ['HTTP_HX_REQUEST' => 'true'],
        );

        self::assertResponseNotHasHeader('hx-refresh');
    }

    private function login(string $fixtureKey): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture($fixtureKey)->email,
            'password' => 'password',
        ]));
    }
}
