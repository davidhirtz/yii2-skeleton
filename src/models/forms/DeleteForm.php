<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use Override;
use Yii;
use yii\base\Model;

class DeleteForm extends Model
{
    public function __construct(
        public readonly ActiveRecord $model,
        public readonly ?string $attribute = null,
        public ?string $value = null,
    ) {
        parent::__construct();
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['value'],
                'required',
                'when' => fn () => $this->attribute
            ],
            [
                ['value'],
                $this->validateValue(...),
            ],
        ];
    }

    public function validateValue(): void
    {
        if ($this->value !== $this->model->{$this->attribute}) {
            $this->addError('value', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->model->getAttributeLabel($this->attribute),
            ]));
        }
    }

    public function delete(): bool
    {
        if (!$this->validate() || !$this->model->delete()) {
            $this->addErrors($this->model->getErrors());
        }

        return !$this->hasErrors();
    }

    public function getId(): array|int|string
    {
        return $this->model->getPrimaryKey();
    }

    #[\Override]
    public function formName(): string
    {
        return 'DeleteForm';
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'value' => $this->model->getAttributeLabel($this->attribute),
        ];
    }

    public static function create(array $config): static
    {
        return Yii::$container->get(static::class, $config);
    }
}
