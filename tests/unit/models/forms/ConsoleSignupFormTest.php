<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use Hirtz\Skeleton\models\forms\ConsoleSignupForm;
use Hirtz\Skeleton\tests\support\UnitTester;
use Yii;

/**
 * @property UnitTester $tester
 */
class ConsoleSignupFormTest extends Unit
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
