<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Log;

use Yii;
use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\log\Logger;

class ActiveRecordErrorLogger extends BaseObject
{
    public function __construct(
        protected ActiveRecord $model,
        protected ?string $message = null,
        protected int $level = Logger::LEVEL_WARNING,
        protected string $category = 'application'
    ) {
        parent::__construct();
    }

    public function init(): void
    {
        if (!$this->message) {
            $modelName = Inflector::camel2words($this->model->formName());
            $verb = $this->model->getIsNewRecord() ? 'inserted' : 'updated';
            $id = implode('-', $this->model->getPrimaryKey(true));
            $this->message = ("$modelName record with ID $id could not be $verb");
        }

        parent::init();
    }

    public function logErrors(): void
    {
        $message = $this->message . $this->getErrors();
        Yii::getLogger()->log($message, $this->level, $this->category);
    }

    protected function getErrors(): string
    {
        if ($errors = $this->model->getErrors()) {
            return PHP_EOL . print_r($errors, true);
        }

        return '';
    }

    public static function log(?ActiveRecord $model, ?string $message = null): void
    {
        $logger = Yii::createObject(static::class, [
            'model' => $model,
            'message' => $message,
        ]);

        $logger->logErrors();
    }
}
