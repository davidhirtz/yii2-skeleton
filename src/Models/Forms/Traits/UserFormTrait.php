<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms\Traits;

use Override;

trait UserFormTrait
{
    public ?string $repeatPassword = null;

    #[Override]
    public function load($data, $formName = null): bool
    {
        $this->user->load($data, $formName);
        return parent::load($data, $formName);
    }

    #[Override]
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $this->clearErrors();

        if (!$this->user->validate($attributeNames, $clearErrors)) {
            $this->addErrors($this->user->getErrors());
        }

        return parent::validate($attributeNames, false);
    }
    public function save(): bool
    {
        if (!$this->validate() || !$this->beforeSave()) {
            return false;
        }

        if ($this->user->upsert(false)) {
            $this->afterSave();
            return true;
        }

        return false;
    }
}
