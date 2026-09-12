<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Db\ActiveRecord;
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
        if (!$this->isValidValue()) {
            $this->addError('value', Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->model->getAttributeLabel($this->attribute),
            ]));
        }
    }

    protected function isValidValue(): bool
    {
        return $this->value === $this->getExpectedValue();
    }

    /**
     * The form renders this as the input's `pattern`, so a secret that is verified rather than compared must
     * return `null` and override {@see static::isValidValue()}.
     */
    public function getExpectedValue(): ?string
    {
        return $this->attribute !== null ? (string)$this->model->{$this->attribute} : null;
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

    #[Override]
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
