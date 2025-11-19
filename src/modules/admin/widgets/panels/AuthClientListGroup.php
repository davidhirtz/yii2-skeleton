<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\panels\Stack;
use davidhirtz\yii2\skeleton\widgets\panels\StackItem;
use davidhirtz\yii2\skeleton\widgets\Widget;
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
