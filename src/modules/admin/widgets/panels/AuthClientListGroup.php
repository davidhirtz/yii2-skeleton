<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroup;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroupItem;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;

class AuthClientListGroup extends Widget
{
    protected function renderContent(): string|Stringable
    {
        return ListGroup::make()
            ->items(...$this->getItems());
    }

    protected function getItems(): array
    {
        return [
            $this->getFacebookLogin(),
        ];
    }

    protected function getFacebookLogin(): ?ListGroupItem
    {
        return (new LoginForm())->isFacebookLoginEnabled()
            ? ListGroupItem::make()
                ->label('Facebook')
                ->icon('brand:facebook')
                ->url(Url::toRoute(['/admin/user/auth', 'authclient' => 'facebook']))
            : null;
    }
}
