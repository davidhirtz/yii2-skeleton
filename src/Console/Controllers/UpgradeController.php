<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;
use yii\helpers\Console;

/**
 * Migrates data left behind by an older major version.
 */
class UpgradeController extends Controller
{
    /**
     * Updates the namespaces stored in the migration and trail tables.
     */
    public function actionIndex(): void
    {
        $this->updateMigrationNamespaces();
        $this->updateTrailNamespaces();
    }

    /**
     * Mails a password reset link to every enabled user without a password.
     *
     * After `M260913180000PasswordScheme` that is everyone who still had a v2 password. Kept out of the migration on
     * purpose: a migration runs in CI and on staging, and must not send mail. A user whose link has not expired yet
     * is skipped, so a run can be repeated for the ones a failing mail server left out.
     */
    public function actionPasswords(): int
    {
        $users = User::find()
            ->where(['password_hash' => null])
            ->andWhere(['!=', 'status', User::STATUS_DISABLED])
            ->all();

        if (!$users) {
            $this->stdout('No users without a password found.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $count = count($users);

        if ($this->interactive && !$this->confirm("Send a password reset link to $count user(s)?", true)) {
            return ExitCode::OK;
        }

        $form = PasswordRecoverForm::create();
        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            if ($this->hasUnexpiredPasswordResetToken($user)) {
                $this->stdout(" > Skipped $user->email, whose link has not expired" . PHP_EOL);
                continue;
            }

            $form->user = $user;
            $form->email = $user->email;

            try {
                $isSent = $form->sendPasswordResetEmail();
            } catch (InvalidConfigException $exception) {
                // A console application has no request to take the host from, and Yii refuses to guess one
                $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);
                $this->stderr('Set `params.hostInfo` (or `components.urlManager.hostInfo` and `baseUrl` for the'
                    . ' console application), so the emailed link knows where it points.' . PHP_EOL, Console::FG_YELLOW);

                return ExitCode::CONFIG;
            }

            if (!$isSent) {
                // A link nobody received must not keep the user out of the next run
                $user->getLatestToken(UserToken::TYPE_PASSWORD_RESET)?->delete();
                $this->stderr(" > Failed to send to $user->email" . PHP_EOL, Console::FG_RED);
                $failed++;

                continue;
            }

            $this->stdout(" > Sent to $user->email" . PHP_EOL);
            $sent++;
        }

        $formatter = Yii::$app->getFormatter();
        $this->stdout('Sent ' . $formatter->asInteger($sent) . ' password reset link(s).' . PHP_EOL, Console::FG_GREEN);

        if ($failed) {
            $this->stderr('Failed to send ' . $formatter->asInteger($failed) . ' link(s).' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    private function hasUnexpiredPasswordResetToken(User $user): bool
    {
        return UserToken::find()
            ->whereUser($user->id)
            ->whereType(UserToken::TYPE_PASSWORD_RESET)
            ->unexpired()
            ->exists();
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
