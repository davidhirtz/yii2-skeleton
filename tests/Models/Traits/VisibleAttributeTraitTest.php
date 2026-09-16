<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Model;

class VisibleAttributeTraitTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
    }

    public function testADeclaredAttributeIsVisible(): void
    {
        $model = $this->createModel(VisibleModel::TYPE_DEFAULT);

        self::assertTrue($model->isAttributeVisible('name'));
        self::assertSame('Name', $model->getVisibleAttribute('name'));
    }

    public function testTheTypeHidesAField(): void
    {
        $model = $this->createModel(VisibleModel::TYPE_WITHOUT_NAME);

        self::assertFalse($model->isAttributeVisible('name'));
        self::assertFalse($model->getVisibleAttribute('name'));

        self::assertTrue($model->isAttributeVisible('content'));
        self::assertSame('Content', $model->getVisibleAttribute('content'));
    }

    /**
     * A custom attribute the type or the subclass leaves out is not declared at all, which a renderer must not tell
     * apart from a hidden one.
     */
    public function testAnUndeclaredAttributeIsNotVisible(): void
    {
        $model = $this->createModel(VisibleModel::TYPE_DEFAULT);

        self::assertNotContains('subtitle', $model->attributes());
        self::assertTrue($model->isAttributeVisible('subtitle'));
        self::assertFalse($model->getVisibleAttribute('subtitle'));
    }

    public function testTheTranslatedValueIsReturned(): void
    {
        $model = $this->createModel(VisibleModel::TYPE_DEFAULT);
        $model->name_de = 'Name (DE)';

        Yii::$app->language = 'de';

        self::assertSame('Name (DE)', $model->getVisibleAttribute('name'));
    }

    private function createModel(int $type): VisibleModel
    {
        $model = new VisibleModel();
        $model->type = $type;
        $model->name = 'Name';
        $model->content = 'Content';

        return $model;
    }
}

class VisibleModel extends Model implements TypeAttributeInterface, VisibleAttributeInterface
{
    use I18nAttributesTrait;
    use ModelTrait;
    use TypeAttributeTrait;
    use VisibleAttributeTrait;

    final public const int TYPE_WITHOUT_NAME = 2;

    public ?int $type = self::TYPE_DEFAULT;
    public ?string $name = null;
    public ?string $name_de = null;
    public ?string $content = null;

    public function __construct(array $config = [])
    {
        $this->i18nAttributes = ['name'];
        parent::__construct($config);
    }

    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default'),
            Type::make(self::TYPE_WITHOUT_NAME)
                ->name('Without name')
                ->hiddenFields('name'),
        ];
    }
}
