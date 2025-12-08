<?php

declare(strict_types=1);

use Hirtz\Skeleton\Models\User;
use yii\db\Expression;

return [
    'owner' => [
        'id' => 1,
        'status' => User::STATUS_ENABLED,
        'name' => 'owner',
        'email' => 'owner@domain.com',
        'timezone' => null,
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => null,
        'auth_key' => 'h0rrnZTn0qKf2mWAxfzb6-cp0XuZRnUh',
        'verification_token' => null,
        'is_owner' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'disabled' => [
        'id' => 2,
        'status' => User::STATUS_DISABLED,
        'name' => 'disabled',
        'email' => 'disabled@domain.com',
        'timezone' => null,
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => null,
        'auth_key' => 'h0rrnZTn0qKf2mWAxfzb6-cp0XuZRnUh',
        'verification_token' => null,
        'is_owner' => 0,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'admin' => [
        'id' => 3,
        'status' => User::STATUS_ENABLED,
        'name' => 'admin',
        'email' => 'f2a@domain.com',
        'timezone' => 'Europe/Berlin',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => 'AX7CR435GC575V4C', // 123456
        'auth_key' => 'h0rrnZTn0qKf2mWAxfzb6-cp0XuZRnUh',
        'verification_token' => 'fLZHyuassSUGwwuFiWHt_NwBXxM3zsSh',
        'is_owner' => 0,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
];
