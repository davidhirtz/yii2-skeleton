<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Models\User;
use Override;

class AccountDeleteForm extends DeleteForm
{
    public function __construct(public readonly User $user, ?string $value = null)
    {
        parent::__construct($user, 'password', $value);
    }

    #[Override]
    protected function isValidValue(): bool
    {
        return $this->user->validatePassword((string)$this->value);
    }

    #[Override]
    public function getExpectedValue(): ?string
    {
        return null;
    }
}
