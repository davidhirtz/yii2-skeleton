<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use Override;
use Yii;
use yii\base\Model;

class MaintenanceConfigForm extends Model
{
    use ModelTrait;

    public const string MAINTENANCE_CONFIG = '@runtime/maintenance.json';

    /**
     * @var string|null optional redirect URL
     */
    public ?string $redirect = null;

    /**
     * @var int|null number of seconds after which the crawler should retry
     */
    public ?int $retry = null;

    /**
     * @var int|null interval in seconds after which the page is refreshed
     */
    public ?int $refresh = null;

    /**
     * @var int HTTP status code
     */
    public int $statusCode = 503;

    /**
     * @var string path to the maintenance mode template, set empty string to render nothing
     */
    public string $viewFile = '@skeleton/views/maintenance.php';

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['retry', 'refresh'],
                'integer',
            ],
            [
                ['redirect', 'viewFile'],
                'string',
            ],
            [
                ['viewFile'],
                function ($attribute): void {
                    if ($this->$attribute && !file_exists(Yii::getAlias($this->$attribute))) {
                        $this->addError($attribute, 'The view file does not exist.');
                    }
                },
            ],
            [
                ['statusCode'],
                'in',
                'range' => [200, 301, 302, 303, 307, 308, 404, 410, 500, 503],
            ],
        ];
    }

    public function save(): false|int
    {
        if ($this->validate()) {
            $config = [
                'redirect' => $this->redirect,
                'retry' => $this->retry,
                'refresh' => $this->refresh,
                'status' => $this->statusCode,
            ];

            if ($this->viewFile) {
                $config['template'] = Yii::$app->getView()->renderFile(Yii::getAlias($this->viewFile));
            }

            $data = json_encode(array_filter($config), JSON_THROW_ON_ERROR);
            return file_put_contents(Yii::getAlias(static::MAINTENANCE_CONFIG), $data);
        }

        return false;
    }

    public function isConfigured(): bool
    {
        return file_exists(Yii::getAlias(static::MAINTENANCE_CONFIG));
    }
}
