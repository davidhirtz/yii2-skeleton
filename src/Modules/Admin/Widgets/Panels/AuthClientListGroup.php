<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Widgets\Panels\Stack;
use Hirtz\Skeleton\Widgets\Panels\StackItem;
use Hirtz\Skeleton\Widgets\Widget;
use Stringable;

class AuthClientListGroup extends Widget
{
    protected function renderContent(): string|Stringable
    {
        return Stack::make()
            ->items(...$this->getItems());
    }

    protected function getItems(): array
    {
        return [
            $this->getFacebookLogin(),
        ];
    }

    protected function getFacebookLogin(): ?StackItem
    {
        return (new LoginForm())->isFacebookLoginEnabled()
            ? StackItem::make()
                ->label('Facebook')
                ->icon('brand:facebook')
                ->url(Url::toRoute(['/admin/user/auth', 'authclient' => 'facebook']))
            : null;
    }
}
