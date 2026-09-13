<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

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

        return $this->db->createCommand()
            ->delete($this->sessionTable, $condition)
            ->execute();
    }
}
