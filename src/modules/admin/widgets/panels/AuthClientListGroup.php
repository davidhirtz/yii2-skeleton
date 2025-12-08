<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\panels;

use Hirtz\Skeleton\helpers\Url;
use Hirtz\Skeleton\models\forms\LoginForm;
use Hirtz\Skeleton\widgets\panels\Stack;
use Hirtz\Skeleton\widgets\panels\StackItem;
use Hirtz\Skeleton\widgets\Widget;
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
