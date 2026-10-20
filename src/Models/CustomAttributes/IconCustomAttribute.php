<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\IconHelper;
use Override;
use Yii;
use yii\base\Model;

class IconCustomAttribute extends SelectCustomAttribute
{
    protected string $path = '/images/icons/';
    protected bool $withVersion = false;

    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function withVersion(bool $withVersion = true): static
    {
        $this->withVersion = $withVersion;
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

    /**
     * A file the deployment has not published yet answers without its time rather than with a warning.
     */
    public function getUrl(ActiveRecord $owner, string $name): ?string
    {
        $filename = $owner->getAttribute($name);

        if (!is_string($filename) || $filename === '') {
            return null;
        }

        $url = $this->path . rawurlencode($filename);
        $time = $this->withVersion ? @filemtime(Yii::getAlias('@webroot') . $this->path . $filename) : false;

        return $time === false ? $url : "$url?$time";
    }
}
