<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms\Traits;

use Override;

trait UserFormTrait
{
    #[Override]
    public function load($data, $formName = null): bool
    {
        $this->user->load($this->filterUserData($data, $formName), $formName);
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

    protected function beforeSave(): bool
    {
        return true;
    }

    protected function afterSave(): void
    {
    }

    /**
     * A form that renders only some of the user's fields must name them here, or the others can still be set through
     * a crafted request.
     *
     * @return list<string>|null the loadable user attributes, `null` for every safe attribute
     */
    protected function getUserAttributeNames(): ?array
    {
        return null;
    }

    private function filterUserData(mixed $data, ?string $formName): mixed
    {
        $names = $this->getUserAttributeNames();
        $formName ??= $this->user->formName();

        if ($names === null || !is_array($data) || !isset($data[$formName]) || !is_array($data[$formName])) {
            return $data;
        }

        $data[$formName] = array_intersect_key($data[$formName], array_flip($names));
        return $data;
    }
}
