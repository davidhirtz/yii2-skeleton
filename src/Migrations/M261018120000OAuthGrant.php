<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Override;
use yii\db\Migration;

/**
 * @noinspection PhpUnused
 */
class M261018120000OAuthGrant extends Migration
{
    #[Override]
    public function safeUp(): void
    {
        $this->execute(
            <<<'SQL'
            CREATE TABLE `oauth_grant` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` int(11) unsigned NOT NULL,
              `client_id` varchar(255) NOT NULL,
              `client_name` varchar(100) DEFAULT NULL,
              `resource` varchar(255) NOT NULL,
              `scope` varchar(32) NOT NULL,
              `last_used_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_id` (`user_id`,`client_id`,`resource`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $this->execute(
            <<<'SQL'
            ALTER TABLE `oauth_grant` ADD CONSTRAINT `oauth_grant_user_id_ibfk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            SQL
        );

        $this->execute(
            <<<'SQL'
            ALTER TABLE `user_token` ADD `grant_id` int(11) unsigned DEFAULT NULL AFTER `user_id`
            SQL
        );

        $this->execute(
            <<<'SQL'
            ALTER TABLE `user_token` ADD CONSTRAINT `user_token_grant_id_ibfk` FOREIGN KEY (`grant_id`) REFERENCES `oauth_grant` (`id`) ON DELETE CASCADE
            SQL
        );
    }

    #[Override]
    public function safeDown(): void
    {
        $this->dropForeignKey('user_token_grant_id_ibfk', '{{%user_token}}');
        $this->dropColumn('{{%user_token}}', 'grant_id');
        $this->dropTable('{{%oauth_grant}}');
    }
}
