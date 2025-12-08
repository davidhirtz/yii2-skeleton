<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\support;

use Codeception\Actor;
use Hirtz\Skeleton\models\User;

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
class FunctionalTester extends Actor
{
    use _generated\FunctionalTesterActions;
    use TesterTrait;

    public function seeValidationError(string $message): void
    {
        $this->see($message, '.form-error,[data-alert="danger"]');
    }

    public function dontSeeValidationError(string $message): void
    {
        $this->dontSee($message, '.form-error,[data-alert="danger"]');
    }
}
