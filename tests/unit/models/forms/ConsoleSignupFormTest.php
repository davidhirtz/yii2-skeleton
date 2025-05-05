<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\models\forms\ConsoleSignupForm;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Yii;

/**
 * @property UnitTester $tester
 */
class ConsoleSignupFormTest extends Unit
{
    public function testSignup(): void
    {
        Yii::$app->language = 'de';

        $form = ConsoleSignupForm::create();
        $form->name = 'Testname';
        $form->email = 'test@test.de';
        $form->password = 'password';

        self::assertTrue($form->insert());
        self::assertTrue($form->user->isOwner());
        self::assertEquals('de', $form->user->language);
    }
}
