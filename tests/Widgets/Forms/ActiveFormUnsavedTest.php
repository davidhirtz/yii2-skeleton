<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\AccountUpdateForm;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\AccountActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\LoginActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\UserActiveForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Yii;
use yii\base\DynamicModel;

class ActiveFormUnsavedTest extends TestCase
{
    use UserFixtureTrait;

    public function testAFormAsksBeforeItsChangesAreLeft(): void
    {
        $html = ActiveForm::make()->model(new DynamicModel(['name' => '']))->render();

        self::assertStringContainsString('data-unsaved="' . Yii::t('skeleton', 'FORM_UNSAVED_CHANGES_CONFIRM') . '"', $html);
        self::assertStringNotContainsString('data-dirty', $html);
    }

    public function testAFormRenderedWithUnsavedInputStartsOutChanged(): void
    {
        $model = new DynamicModel(['name' => '']);
        $model->addError('name', 'Name cannot be blank.');

        self::assertStringContainsString('data-dirty', ActiveForm::make()->model($model)->render());

        $this->getWebRequest()->getHeaders()->set('X-Form-Reload', '1');

        self::assertStringContainsString('data-dirty', ActiveForm::make()->model(new DynamicModel(['name' => '']))->render());
    }

    public function testOnlyASavedRecordsFormStartsOutPristine(): void
    {
        $redirect = Redirect::create();
        $redirect->request_uri = 'old-page';
        $redirect->url = 'new-page';

        self::assertStringNotContainsString('data-pristine', ActiveForm::make()->model($redirect)->render());

        self::assertTrue($redirect->insert(), print_r($redirect->getErrors(), true));
        self::assertStringContainsString('data-pristine', ActiveForm::make()->model($redirect)->render());

        $redirect->addError('url', 'URL is invalid.');
        self::assertStringNotContainsString('data-pristine', ActiveForm::make()->model($redirect)->render());
    }

    public function testTheUserFormsStartOutPristineForASavedUser(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->login($owner);

        $user = $this->getUserFromFixture('disabled');

        self::assertStringContainsString('data-pristine', UserActiveForm::make()->model(new UserForm($user))->render());
        self::assertStringNotContainsString('data-pristine', UserActiveForm::make()->model(new UserForm(User::create()))->render());
        self::assertStringContainsString('data-pristine', AccountActiveForm::make()->model(new AccountUpdateForm($owner))->render());
    }

    public function testASignInFormAndAReadOnlyOneDoNot(): void
    {
        self::assertStringNotContainsString('data-unsaved', LoginActiveForm::make()->model(LoginForm::create())->render());
        self::assertStringNotContainsString('data-unsaved', ActiveForm::make()->model(new DynamicModel(['name' => '']))->readonly()->render());
    }
}
