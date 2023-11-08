<?php

use davidhirtz\yii2\skeleton\models\User;
use yii\db\Expression;

return [
    'owner' => [
        'status' => User::STATUS_ENABLED,
        'name' => 'owner',
        'email' => 'owner@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'is_owner' => 1,
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'disabled' => [
        'status' => User::STATUS_DISABLED,
        'name' => 'disabled',
        'email' => 'disabled@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
    'f2a' => [
        'status' => User::STATUS_ENABLED,
        'name' => 'f2a',
        'email' => 'f2a@domain.com',
        'password_hash' => '$2y$13$fsHsH/ZbpVdOY85BaAsW8uWv12zR7NuzHYtgYE0qBtPzQmcjB.a1a', // password
        'password_salt' => 'tVe8JqR-jI',
        'google_2fa_secret' => 'AX7CR435GC575V4C',
        'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'created_at' => new Expression('UTC_TIMESTAMP()'),
    ],
];
