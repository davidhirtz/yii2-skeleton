<?php

namespace davidhirtz\yii2\skeleton\models\interfaces;

interface DraftStatusAttributeInterface extends StatusAttributeInterface
{
    public const STATUS_ENABLED = 3;
    public const STATUS_DRAFT = 1;
    public const STATUS_DISABLED = 0;

    public const STATUS_DEFAULT = self::STATUS_ENABLED;

    public function isDraft(): bool;

}