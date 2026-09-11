<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Helpers\IconHelper;
use Override;
use yii\base\Model;

class IconCustomAttribute extends SelectCustomAttribute
{
    protected string $path = '/images/icons/';

    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    #[Override]
    public function getOptions(Model $owner): array
    {
        return $this->options ? parent::getOptions($owner) : IconHelper::getIconFilenames($this->path);
    }

    #[Override]
    protected function getFingerprintData(): array
    {
        return [...parent::getFingerprintData(), $this->path];
    }
}
