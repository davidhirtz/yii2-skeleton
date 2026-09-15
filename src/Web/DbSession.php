<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use yii\db\Connection;
use yii\di\Instance;

class DbSession extends \yii\web\DbSession
{
    use SessionTrait;

    /**
     * @return int the number of sessions destroyed
     */
    public function destroyUserSessions(int $userId, ?string $exceptId = null): int
    {
        $condition = ['user_id' => $userId];

        if ($exceptId !== null && $exceptId !== '') {
            $condition = ['and', $condition, ['not', ['id' => $exceptId]]];
        }

        return Instance::ensure($this->db, Connection::class)->createCommand()
            ->delete($this->sessionTable, $condition)
            ->execute();
    }
}
