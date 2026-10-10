<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261010120000Consent extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->execute(
            <<<'SQL'
            CREATE TABLE `consent` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `uuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              `version` varchar(32) NOT NULL,
              `categories` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`categories`)),
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `uuid` (`uuid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropTable('{{%consent}}');
    }
}
