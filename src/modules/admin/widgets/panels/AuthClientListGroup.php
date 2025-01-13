<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\Widget;

class AuthClientListGroup extends Widget
{
    protected ListGroup $list;

    public function init(): void
    {
        $this->list = ListGroup::make();
        $this->setItems();

        parent::init();
    }

    public function render(): string
    {
        return $this->list->render();
    }

    protected function setItems(): void
    {
        $this->setFacebookLoginLink();
    }

    protected function setFacebookLoginLink(): void
    {
        if ((new LoginForm())->isFacebookLoginEnabled()) {
            $this->list->addItem(ListGroupItemLink::make()
                ->text('Facebook')
                ->icon('brand:facebook')
                ->href(Url::toRoute(['/admin/user/auth', 'authclient' => 'facebook'])));
        }
    }
}
