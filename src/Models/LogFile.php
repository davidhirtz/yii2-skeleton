<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use yii\base\Model;

class LogFile extends Model
{
    use ModelTrait;

    public string $name;
    public int $size;
    public int $updated_at;

    public static function createFromFilename(string $filename): static
    {
        $log = static::create();
        $log->name = pathinfo($filename, PATHINFO_BASENAME);
        $log->size = filesize($filename) ?: 0;
        $log->updated_at = filemtime($filename) ?: 0;

        return $log;
    }
}
