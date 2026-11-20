<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\Application;
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
        $webuser = Application::current()->getUser();

        // Guessed against the login's attempt limit, as a hijacked session would
        if ($webuser->isLoginAttemptLimitReached($this->user->email)) {
            return false;
        }

        // Trimmed like the login, which accepted it
        if ($this->user->validatePassword(trim((string)$this->value))) {
            return true;
        }

        $webuser->addFailedLoginAttempt($this->user->email);
        return false;
    }

    #[Override]
    public function getExpectedValue(): ?string
    {
        return null;
    }
}
