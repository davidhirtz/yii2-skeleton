<?php

declare(strict_types=1);

return [
    [
        'id' => 1,
        'type' => 1,
        'model' => 'Hirtz\Skeleton\models\User',
        'model_id' => '1',
        'user_id' => 1,
        'message' => null,
        'data' => '{"status":1,"name":"Test","email":"name@test.com","language":"en-US","timezone":"Europe\/Berlin","is_owner":true}',
        'created_at' => gmdate("Y-m-d H:i:s", strtotime("-1 year")),
    ],
    [
        'id' => 2,
        'type' => 2,
        'model' => 'Hirtz\Skeleton\models\User',
        'model_id' => '1',
        'user_id' => 1,
        'message' => null,
        'data' => '{"language":["en-US","de"]}',
        'created_at' => gmdate("Y-m-d H:i:s", strtotime("-6 months")),

    ],
    [
        'id' => 3,
        'type' => 1,
        'model' => 'Hirtz\Skeleton\models\Redirect',
        'model_id' => '1',
        'user_id' => 1,
        'message' => null,
        'data' => '{"type":301,"request_uri":"test-request","url":"test-target"}',
        'created_at' => gmdate("Y-m-d H:i:s", strtotime("-1 day")),
    ],
    [
        'id' => 4,
        'type' => 2,
        'model' => 'Hirtz\Skeleton\models\Redirect',
        'model_id' => '1',
        'user_id' => 1,
        'message' => null,
        'data' => '{"url":["test-target","new-test-target"]}',
        'created_at' => gmdate("Y-m-d H:i:s", strtotime("-2 minutes")),
    ],
    [
        "id" => 5,
        "type" => 4,
        "model" => 'invalid\namespace\models\Model',
        "model_id" => "1",
        "user_id" => 1,
        "message" => null,
        "data" => null,
        "created_at" => gmdate("Y-m-d H:i:s", strtotime("-1 minute")),
    ],
];
