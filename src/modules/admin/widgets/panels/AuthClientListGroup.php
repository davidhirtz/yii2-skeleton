<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroup;
use davidhirtz\yii2\skeleton\widgets\panels\ListGroupItem;
use davidhirtz\yii2\skeleton\widgets\Widget;

/**
 * @todo
 */
class AuthClientListGroup extends Widget
{
    protected ListGroup $list;

    protected function renderContent(): string
    {
        $this->list = ListGroup::make();
        $this->setItems();

        return $this->list->render();
    }

    protected function setItems(): void
    {
        $this->setFacebookLoginLink();
    }

    protected function setFacebookLoginLink(): void
    {
        if ((new LoginForm())->isFacebookLoginEnabled()) {
            $this->list->addItem(ListGroupItem::make()
                ->label('Facebook')
                ->icon('brand:facebook')
                ->url(Url::toRoute(['/admin/user/auth', 'authclient' => 'facebook'])));
        }
    }
}
