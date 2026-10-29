<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\UserIdentityTrait;
use Hirtz\Skeleton\Setup\Setup;
use Override;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Creates the first content of an installation.
 */
class SetupController extends Controller
{
    use UserIdentityTrait;

    /**
     * @var string|null the file returning the content to insert by seeder key, `@root/config/setup.php` when it is not
     * given
     */
    public ?string $file = null;

    /**
     * @param string $actionID
     * @return list<string>
     */
    #[Override]
    public function options($actionID): array
    {
        return array_values([...parent::options($actionID), 'file', 'user']);
    }

    #[Override]
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action) && $this->setUserIdentity();
    }

    /**
     * Runs every registered seeder on its key of the setup file.
     */
    public function actionIndex(): int
    {
        $path = Yii::getAlias($this->file ?? '@root/config/setup.php');

        if ($this->file !== null && !is_file($path)) {
            $this->stderr("File $path not found." . PHP_EOL, Console::FG_RED);
            return ExitCode::NOINPUT;
        }

        /** @var array<array-key, mixed> $config */
        $config = is_file($path) ? require $path : [];
        $setup = Setup::getComponent();

        if ($keys = $setup->getUnknownKeys($config)) {
            $this->stderr('No seeder reads ' . implode(', ', $keys) . '.' . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $success = true;

        foreach ($setup->run($config) as $seeder) {
            foreach ($seeder->getInserted() as $record) {
                $id = implode('-', (array)$record->getPrimaryKey());
                $this->stdout('Inserted ' . $record::class . " #$id" . PHP_EOL);
            }

            foreach ($seeder->getFailed() as $record) {
                $errors = implode(' ', $record->getErrorSummary(true));
                $this->stderr('Failed to insert ' . $record::class . ": $errors" . PHP_EOL, Console::FG_RED);
                $success = false;
            }
        }

        return $success ? ExitCode::OK : ExitCode::DATAERR;
    }
}
