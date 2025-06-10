<?php

namespace davidhirtz\yii2\skeleton\tests\support;

use davidhirtz\yii2\skeleton\models\User;

trait TesterTrait
{
    public function grabUserFixture(string $index = 'owner'): User
    {
        return $this->grabFixture('user', $index);
    }
}