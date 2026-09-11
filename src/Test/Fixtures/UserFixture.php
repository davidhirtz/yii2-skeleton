<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Test\Fixtures;

use Hirtz\Skeleton\Models\User;

class UserFixture extends ActiveFixture
{
    public $modelClass = User::class;
}
