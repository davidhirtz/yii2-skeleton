<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Fields;

use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Types\Type;
use Override;
use yii\base\Model;

/**
 * Reloads the form when the selected type renders different custom attribute fields. Types sharing one definition
 * list get no `hx-*` attributes at all, so a form without custom attributes never pays for a round trip.
 */
class TypeSelectField extends SelectField
{
    #[Override]
    protected function configure(): void
    {
        $this->property ??= 'type';
        $fingerprints = $this->getFingerprints();

        if (count(array_unique($fingerprints)) > 1) {
            $this->reloadsForm();

            foreach ($fingerprints as $type => $fingerprint) {
                $this->itemAttributes[$type]['data-fingerprint'] ??= $fingerprint;
            }

            $this->attributes['data-fingerprint'] ??= $fingerprints[$this->model->{$this->property}] ?? '';
            $this->attributes['hx-trigger'] ??= 'change[this.selectedOptions[0].dataset.fingerprint !== this.dataset.fingerprint]';
        }

        parent::configure();
    }

    /**
     * A type the record cannot take is not offered, which is what {@see Type::available()} is for — except the
     * one the record is stored with, or the form would silently retype it on the next save.
     */
    #[Override]
    protected function getItemsFromModel(): array
    {
        return array_filter(
            parent::getItemsFromModel(),
            fn (mixed $item): bool => !$item instanceof Type
                || $item->isAvailableOrStored($this->model, (string)$this->property),
        );
    }

    /**
     * @return array<int|string, string>
     */
    protected function getFingerprints(): array
    {
        $model = $this->model;

        if (!$model instanceof TypeAttributeInterface || !$model instanceof CustomAttributeInterface) {
            return [];
        }

        return $this->getModelFingerprints($model);
    }

    /**
     * A type instance carries no relation, so a relation-dependent definition has to fingerprint the same for every
     * type — only the difference between the types decides whether the form reloads.
     *
     * @return array<int, string>
     */
    private function getModelFingerprints(Model&TypeAttributeInterface&CustomAttributeInterface $model): array
    {
        $fingerprints = [];

        foreach ($model::getTypeInstances() as $type => $instance) {
            if (!$model::findType($type)?->isAvailableOrStored($model, (string)$this->property)) {
                continue;
            }

            $fingerprints[$type] = md5(implode('', array_map(
                static fn (CustomAttribute $definition): string => $definition->getFingerprint(),
                $instance->getCustomAttributeDefinitions(),
            )));
        }

        return $fingerprints;
    }
}
