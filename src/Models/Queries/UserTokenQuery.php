<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Queries;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\UserToken;

/**
 * @template T of UserToken
 * @extends ActiveQuery<T>
 */
class UserTokenQuery extends ActiveQuery
{
    public function whereUser(int $userId): static
    {
        return $this->andWhere([UserToken::tableName() . '.[[user_id]]' => $userId]);
    }

    public function whereType(string $type): static
    {
        return $this->andWhere([UserToken::tableName() . '.[[type]]' => $type]);
    }

    public function whereToken(string $token): static
    {
        return $this->andWhere([UserToken::tableName() . '.[[token]]' => UserToken::hash($token)]);
    }

    /**
     * A `null` expiry never runs out, which is what the recovery codes use.
     */
    public function unexpired(): static
    {
        $tableName = UserToken::tableName();

        return $this->andWhere([
            'or',
            ["$tableName.[[expires_at]]" => null],
            ['>', "$tableName.[[expires_at]]", gmdate('Y-m-d H:i:s')],
        ]);
    }
}
