<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use yii\base\Model;

class Log extends Model
{
    use ModelTrait;

    public string $date = '';
    public string $message = '';
    public string $ip = '';
    public string $user_id = '';
    public string $session_id = '';
    public string $level = '';
    public string $category = '';
    public string $content = '';

    /**
     * @param string[] $data
     */
    public static function createFromData(string $message, array $data): static
    {
        $log = static::create();

        $log->date = $data[1];
        $log->message = trim(substr($message, strlen($data[0]) + 1));
        $log->ip = $data[2];
        $log->user_id = $data[3];
        $log->session_id = $data[4];
        $log->level = $data[5];
        $log->category = $data[6];

        return $log;
    }
}
