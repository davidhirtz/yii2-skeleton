<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;
use Override;
use yii\base\Model;

class LinkCustomAttribute extends UrlCustomAttribute
{
    protected bool $relative = true;

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(LinkField::make(), $owner);
    }

    public function getJsonSchemaComment(): string
    {
        return 'An absolute URL (https:, mailto:, tel:) or a partial one starting with /, ? or #.';
    }
}
