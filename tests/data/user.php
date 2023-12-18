<?php

use davidhirtz\yii2\skeleton\models\User;
use yii\db\Expression;

return [
    'owner' => [
        'id' => 1,
        'status' => User::STATUS_ENABLED,
        'name' => 'owner',
        'email' => 'owner@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => null,
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'is_owner' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'disabled' => [
        'id' => 2,
        'status' => User::STATUS_DISABLED,
        'name' => 'disabled',
        'email' => 'disabled@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => null,
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'is_owner' => 0,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'admin' => [
        'id' => 3,
        'status' => User::STATUS_ENABLED,
        'name' => 'admin',
        'email' => 'f2a@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => 'AX7CR435GC575V4C', // 123456
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'is_owner' => 0,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
];
