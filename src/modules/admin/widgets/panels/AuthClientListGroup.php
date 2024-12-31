<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemAction;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\widgets\Widget;

class AuthClientListGroup extends Widget
{
    protected ListGroup $list;

    public function init(): void
    {
        $this->list = ListGroup::tag();
        $this->setLinks();

        parent::init();
    }

    public function run(): string
    {
        return $this->list->render();
    }

    protected function setLinks(): void
    {
        $this->setFacebookLoginLink();
    }

    protected function setFacebookLoginLink(): void
    {
        if ((new LoginForm())->isFacebookLoginEnabled()) {
            $this->list->link(ListGroupItemAction::tag()
                ->icon('facebook', 'fab')
                ->text('Facebook')
                ->href(Url::toRoute(['/admin/user/auth', 'authclient' => 'facebook'])));
        }
    }
}
