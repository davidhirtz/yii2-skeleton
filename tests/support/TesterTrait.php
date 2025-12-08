<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\support;

use Hirtz\Skeleton\models\User;

trait TesterTrait
{
    public function grabUserFixture(string $index = 'owner'): User
    {
        return $this->grabFixture('user', $index);
    }
}
