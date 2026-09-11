<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Trail;
use Yii;
use yii\console\Controller;
use yii\db\Query;

class UpgradeController extends Controller
{
    public function actionIndex(): void
    {
        $this->updateUserUpdateRule();
        $this->updateMigrationNamespaces();
        $this->updateTrailNamespaces();
    }

    private function updateUserUpdateRule(): void
    {
        $name = 'userUpdateRule';
        $old = 'davidhirtz\\yii2\\skeleton\\rbac\\rules\\OwnerRule';
        $new = \Hirtz\Skeleton\Rbac\Rules\OwnerRule::class;

        $data = (new Query())
            ->select('data')
            ->from(Yii::$app->getAuthManager()->ruleTable)
            ->where(['name' => $name])
            ->scalar();

        $newData = str_replace($old, $new, $data);

        $success = $newData !== $data
            ? Yii::$app->getDb()->createCommand()
                ->update(
                    Yii::$app->getAuthManager()->ruleTable,
                    ['data' => $newData, 'updated_at' => time()],
                    ['name' => $name]
                )
                ->execute()
            : 0;

        $this->stdout($success
            ? " > Updated $name successfully.\n"
            : " > No update needed for $name.\n");
    }

    private function updateMigrationNamespaces(): void
    {
        $classes = (new Query())
            ->select('version')
            ->from('{{%migration}}')
            ->column();

        $count = 0;

        foreach ($classes as $class) {
            $newClass = $this->getNewNamespace($class);

            if ($newClass !== $class) {
                $count += Yii::$app->getDb()->createCommand()
                    ->update('{{%migration}}', ['version' => $newClass], ['version' => $class])
                    ->execute();
            }
        }

        $count = $count ? Yii::$app->getFormatter()->asInteger($count) : null;

        $this->stdout($count
            ? " > Updated $count migration namespaces successfully.\n"
            : " > No update needed for migration namespaces.\n");
    }

    private function updateTrailNamespaces(): void
    {
        $query = Trail::find()
            ->select(['id', 'model_class', 'data'])
            ->asArray();

        $count = 0;

        foreach ($query->each() as $row) {
            $modelClass = $this->getNewNamespace($row['model_class']);
            $data = $row['data'] !== null ? json_decode($row['data'], true) : [];

            if (array_key_exists('model_class', $data)) {
                $data['model_class'] = $this->getNewNamespace($data['model_class']);
            }

            $data = json_encode($data);

            if ($modelClass !== $row['model_class'] || $data !== $row['data']) {
                $count += Yii::$app->getDb()->createCommand()
                    ->update(Trail::tableName(), ['model_class' => $modelClass, 'data' => $data], ['id' => $row['id']])
                    ->execute();
            }
        }

        $count = $count ? Yii::$app->getFormatter()->asInteger($count) : null;

        $this->stdout($count
            ? " > Updated $count trail records successfully.\n"
            : " > No update needed for trail records.\n");
    }

    private function getNewNamespace(string $class): string
    {
        $class = str_replace(['davidhirtz\\yii2\\', 'app\\'], ['Hirtz\\', 'App\\'], $class);
        return implode('\\', array_map(ucfirst(...), explode('\\', $class)));
    }
}
