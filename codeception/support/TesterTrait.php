<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\support;

use Hirtz\Skeleton\Models\User;

trait TesterTrait
{
    public function grabUserFixture(string $index = 'owner'): User
    {
        return $this->grabFixture('user', $index);
    }
}
