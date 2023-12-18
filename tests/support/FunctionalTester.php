<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\support;

use _generated;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    public function seeValidationError(string $message): void
    {
        $this->see($message, '.alert-error');
    }

    public function dontSeeValidationError(string $message): void
    {
        $this->dontSee($message, '.alert-error');
    }
}
