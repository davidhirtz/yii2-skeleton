<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Buttons;

use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Override;

/**
 * @see AccountController::actionContentLanguage()
 */
class ContentLanguageDropdownButton extends LanguageDropdownButton
{
    #[Override]
    protected function getLanguages(): array
    {
        return static::getModule()->getContentLanguages();
    }

    #[Override]
    protected function getCurrentLanguage(): string
    {
        return static::getModule()->getContentLanguage();
    }

    #[Override]
    protected function getRoute(): array
    {
        return ['/admin/account/content-language'];
    }
}
