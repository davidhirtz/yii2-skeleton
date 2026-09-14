<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Migrations;

use Hirtz\Skeleton\Db\Traits\MigrationTrait;
use Hirtz\Skeleton\Models\UserLogin;
use Yii;
use yii\db\Migration;

/**
 * The strings and integers are hardcoded: they are this migration's point in history, and a later rename of the
 * constants must not change what it wrote.
 *
 * @noinspection PhpUnused
 */
class M260914180000UserLoginType extends Migration
{
    use MigrationTrait;

    private const int TYPE_OTHER = 1;

    /**
     * What v2 wrote for a login whose caller named no type, and where the collapsed rows land on the way down.
     */
    private const string NAME_OTHER = 'unknown';

    /**
     * @var array<string, int>
     */
    private const array TYPES = [
        'login' => 2,
        'auto' => 3,
        'signup' => 4,
        'email' => 5,
        'password' => 6,
    ];

    public function safeUp(): void
    {
        $this->addColumn(UserLogin::tableName(), 'type_id', (string)$this->tinyInteger()
            ->unsigned()
            ->notNull()
            ->defaultValue(self::TYPE_OTHER));

        foreach ($this->getTypeMap() as $name => $type) {
            $this->update(UserLogin::tableName(), ['type_id' => $type], ['type' => $name]);
        }

        $this->dropColumn(UserLogin::tableName(), 'type');
        $this->renameColumn(UserLogin::tableName(), 'type_id', 'type');
    }

    /**
     * The provider names the removed social login wrote do not come back: they were collected under
     * {@see UserLogin::TYPE_OTHER} and nothing records which one each row held.
     */
    public function safeDown(): void
    {
        $this->addColumn(UserLogin::tableName(), 'type_name', (string)$this->string(12)
            ->notNull()
            ->defaultValue(self::NAME_OTHER));

        foreach ($this->getTypeMap() as $name => $type) {
            $this->update(UserLogin::tableName(), ['type_name' => $name], ['type' => $type]);
        }

        $this->dropColumn(UserLogin::tableName(), 'type');
        $this->renameColumn(UserLogin::tableName(), 'type_name', 'type');
    }

    /**
     * A project that wrote types of its own — a Shibboleth integration, a second identity provider — maps them
     * through `params['userLoginTypes']`, `['shibboleth' => 7]`, and declares the same values in its
     * `UserLogin::getTypes()` override. Anything left unmapped becomes {@see UserLogin::TYPE_OTHER}.
     *
     * @return array<string, int>
     */
    private function getTypeMap(): array
    {
        /** @var array<string, int> $types */
        $types = Yii::$app->params['userLoginTypes'] ?? [];
        return [...self::TYPES, ...$types];
    }
}
