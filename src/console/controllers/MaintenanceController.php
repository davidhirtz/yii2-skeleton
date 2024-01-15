<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\forms\MaintenanceConfigForm;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Toggles maintenance mode. The command copies the maintenance mode template to the runtime directory. In the
 * `web/index.php` entry script, the existence of the file is checked and the maintenance mode template is displayed if
 * necessary.
 *
 * @since v1.8
 */
class MaintenanceController extends Controller
{
    final public const MAINTENANCE_FILE = '@runtime/maintenance.php';

    /**
     * @var string path to the maintenance mode template stub
     */
    public string $maintenanceStubFile = '@skeleton/console/controllers/stubs/maintenance.stub';

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
    public string $viewFile = '@skeleton/views/maintenance.php';

    /**
     * @var string[] list of properties that can be configured
     */
    protected array $configProperties = [
        'redirect',
        'retry',
        'refresh',
        'statusCode',
        'viewFile',
    ];

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID == 'enable') {
            $options = array_merge($options, $this->configProperties);
        }

        return $options;
    }

    /**
     * Toggles maintenance mode with current configuration.
     */
    public function actionIndex(): void
    {
        if ($this->isMaintenanceMode()) {
            $this->disableMaintenanceMode();
        } else {
            $this->enableMaintenanceMode();
        }
    }

    /**
     * Enables maintenance mode with the given configuration. Run `yii help maintenance/enable` for more information.
     */
    public function actionEnable(): void
    {
        if (!$this->isMaintenanceMode()) {
            $this->enableMaintenanceMode(true);
        }
    }

    /**
     * Disables maintenance mode.
     */
    public function actionDisable(): void
    {
        if ($this->isMaintenanceMode()) {
            $this->disableMaintenanceMode();
        }
    }

    /**
     * Enables maintenance mode with the given configuration by copying the maintenance mode template to the runtime.
     * The configuration is saved in a JSON file in the runtime directory.
     *
     * @uses $redirect
     * @uses $retry
     * @uses $refresh
     * @uses $statusCode
     * @uses $viewFile
     */
    protected function enableMaintenanceMode(bool $withConfig = false): void
    {
        $form = MaintenanceConfigForm::create();

        if ($withConfig || !$form->isConfigured()) {
            foreach ($this->configProperties as $property) {
                $form->$property = $this->$property;
            }

            if (!$form->save()) {
                $this->stdout(Console::errorSummary($form) . PHP_EOL);
                return;
            }
        }

        if (copy(Yii::getAlias($this->maintenanceStubFile), Yii::getAlias(self::MAINTENANCE_FILE))) {
            $this->stdout('Maintenance mode enabled.' . PHP_EOL, Console::FG_GREEN);
        }
    }

    /**
     * Disables maintenance mode by removing the maintenance mode template from the runtime. Keeping the config.
     */
    protected function disableMaintenanceMode(): void
    {
        FileHelper::unlink(Yii::getAlias(self::MAINTENANCE_FILE));
        $this->stdout('Maintenance mode disabled.' . PHP_EOL, Console::FG_GREEN);
    }

    protected function isMaintenanceMode(): bool
    {
        return file_exists(Yii::getAlias(self::MAINTENANCE_FILE));
    }
}
