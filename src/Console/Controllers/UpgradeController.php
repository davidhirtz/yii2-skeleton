<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\db\Query;
use yii\helpers\Console;

/**
 * Migrates data left behind by an older major version.
 */
class UpgradeController extends Controller
{
    public function actionIndex(): void
    {
        $this->updateMigrationNamespaces();
        $this->updateTrailNamespaces();
    }

    /**
     * Mails a password reset link to every user without a password, which after `M260913180000PasswordScheme` is
     * everyone who still had a v2 password. Kept out of the migration on purpose: a migration runs in CI and on
     * staging, and must not send mail.
     */
    public function actionPasswords(): void
    {
        $users = User::find()
            ->where(['password_hash' => null])
            ->all();

        if (!$users) {
            $this->stdout('No users without a password found.' . PHP_EOL, Console::FG_GREEN);
            return;
        }

        $count = count($users);

        if ($this->interactive && !$this->confirm("Send a password reset link to $count user(s)?", true)) {
            return;
        }

        $form = PasswordRecoverForm::create();

        foreach ($users as $user) {
            $form->user = $user;
            $form->email = $user->email;

            try {
                $form->sendPasswordResetEmail();
            } catch (InvalidConfigException $exception) {
                // A console application has no request to take the host from, and Yii refuses to guess one
                $this->stdout($exception->getMessage() . PHP_EOL, Console::FG_RED);
                $this->stdout('Configure `components.urlManager.hostInfo` (and `baseUrl`) for the console'
                    . ' application, so the emailed link knows where it points.' . PHP_EOL, Console::FG_YELLOW);

                return;
            }

            $this->stdout(" > Sent to $user->email" . PHP_EOL);
        }

        $count = Yii::$app->getFormatter()->asInteger($count);
        $this->stdout("Sent $count password reset link(s)." . PHP_EOL, Console::FG_GREEN);
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
