<?php

namespace davidhirtz\yii2\skeleton\db\mysql;

use yii\db\mysql\Schema;

class ColumnSchema extends \yii\db\mysql\ColumnSchema
{
    public function phpTypecast($value): mixed
    {
        $value = parent::phpTypecast($value);

        // Before Yii 2.0.50, JSON columns were not supported for MariaDB. This led to double JSON-decoded values for
        // Json columns in MySQL databases. This check ensures that double encoded JSON content is returned as an array.
        if ($this->type === Schema::TYPE_JSON && is_string($value)) {
            $value = json_decode($value, true);
        }

        return $value;
    }
}
