<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Models\Forms\ConsoleSignupForm;
use Yii;

class ConsoleSignupFormTest extends TestCase
{
    public function testSignup(): void
    {
        Yii::$app->language = 'de';
        Yii::$app->getI18n()->setLanguages(Yii::$app->language);

        $form = ConsoleSignupForm::create();
        $form->name = 'Testname';
        $form->email = 'test@test.de';
        $form->password = 'password';

        self::assertTrue($form->insert());
        self::assertTrue($form->user->isOwner());
        self::assertEquals(Yii::$app->language, $form->user->language);
    }
}
