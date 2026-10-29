<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\Forms\MaintenanceConfigForm;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Toggles maintenance mode.
 *
 * Copies the template to the runtime directory, which `web/index.php` checks for.
 */
class MaintenanceController extends Controller
{
    final public const string MAINTENANCE_FILE = '@runtime/maintenance.php';

    /**
     * @var string path to the maintenance mode template stub
     */
    public string $maintenanceStubFile = '@skeleton/Console/Controllers/Stubs/maintenance.stub';

    /**
     * @var string|null optional redirect URL
     */
    public ?string $redirect = null;

    /**
     * @var int|null number of seconds after which the crawler should retry
     */
    public ?int $retry = 5;

    /**
     * @var int|null interval in seconds after which the page is refreshed
     */
    public ?int $refresh = 5;

    /**
     * @var int HTTP status code
     */
    public int $statusCode = 503;

    /**
     * @var string path to the maintenance mode template, set empty string to render nothing
     */
    public string $viewFile = '@skeleton/../resources/views/maintenance.php';

    /**
     * @var string[]
     */
    protected array $configProperties = [
        'redirect',
        'retry',
        'refresh',
        'statusCode',
        'viewFile',
    ];

    #[\Override]
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'enable') {
            $options = [...$options, ...$this->configProperties];
        }

        return $options;
    }

    /**
     * Toggles maintenance mode with current configuration.
     */
    public function actionIndex(): int
    {
        if ($this->isMaintenanceMode()) {
            return $this->disableMaintenanceMode();
        }

        return $this->enableMaintenanceMode();
    }

    /**
     * Enables maintenance mode with the given configuration.
     */
    public function actionEnable(): int
    {
        return $this->enableMaintenanceMode(true);
    }

    /**
     * Disables maintenance mode.
     */
    public function actionDisable(): int
    {
        return $this->isMaintenanceMode() ? $this->disableMaintenanceMode() : ExitCode::OK;
    }

    /**
     * A failure must exit non-zero, since a deployment chains its next step on it (`maintenance/enable && migrate`).
     *
     * @see $redirect
     * @see $retry
     * @see $refresh
     * @see $statusCode
     * @see $viewFile
     */
    protected function enableMaintenanceMode(bool $withConfig = false): int
    {
        $isEnabled = $this->isMaintenanceMode();
        $form = MaintenanceConfigForm::create();

        if ($withConfig || !$form->isConfigured()) {
            foreach ($this->configProperties as $property) {
                $form->$property = $this->$property;
            }

            if ($form->save() === false) {
                $this->stderr(($form->hasErrors()
                    ? Console::errorSummary($form)
                    : 'Could not write ' . Yii::getAlias($form::MAINTENANCE_CONFIG) . '.') . PHP_EOL, Console::FG_RED);

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $file = Yii::getAlias(self::MAINTENANCE_FILE);
        FileHelper::createDirectory(dirname($file));

        if (!copy(Yii::getAlias($this->maintenanceStubFile), $file)) {
            $this->stderr("Could not write $file." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(($isEnabled ? 'Maintenance mode updated.' : 'Maintenance mode enabled.') . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Keeps the saved configuration.
     */
    protected function disableMaintenanceMode(): int
    {
        if (!FileHelper::unlink(Yii::getAlias(self::MAINTENANCE_FILE))) {
            $this->stderr('Could not disable maintenance mode.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Maintenance mode disabled.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    protected function isMaintenanceMode(): bool
    {
        return file_exists(Yii::getAlias(self::MAINTENANCE_FILE));
    }
}
