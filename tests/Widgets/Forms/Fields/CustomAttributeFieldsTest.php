<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms\Fields;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\CustomAttributes\BooleanCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\GroupField;
use Hirtz\Skeleton\Widgets\Forms\Fields\TypeSelectField;
use Hirtz\Skeleton\Widgets\Forms\Fieldset;
use Override;
use Yii;

class CustomAttributeFieldsTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        Yii::$app->getDb()->createCommand()
            ->createTable(FieldRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    /**
     * `CREATE TABLE` commits the test transaction, so the records are removed by hand.
     */
    #[Override]
    protected function tearDown(): void
    {
        Translation::deleteAll(['model' => FieldRecord::class]);

        Yii::$app->getDb()->createCommand()
            ->dropTable(FieldRecord::tableName())
            ->execute();

        parent::tearDown();
    }

    public function testScalarFieldsRenderPerLanguageAndByType(): void
    {
        $model = $this->createRecord();
        $model->subtitle = 'Subtitle';
        $model->subtitle_de = 'Subtitle DE';

        $content = Fieldset::make()
            ->model($model)
            ->rows(['subtitle', 'featured', 'layout', 'tags', 'locked', 'secret'])
            ->render();

        self::assertStringContainsString(
            '<input type="text" id="fieldrecord-subtitle" class="input" name="FieldRecord[subtitle]" value="Subtitle" maxlength="20">',
            $content
        );

        self::assertStringContainsString(
            '<input type="text" id="fieldrecord-subtitle-de" class="input" name="FieldRecord[subtitle_de]" value="Subtitle DE" maxlength="20">',
            $content
        );

        self::assertStringContainsString('Subtitle (DE)', $content);

        // The checkbox writes its unchecked value through a hidden input.
        self::assertStringContainsString(
            '<input type="hidden" name="FieldRecord[featured]" value="0">',
            $content
        );

        self::assertStringContainsString(
            '<select id="fieldrecord-tags" class="input" name="FieldRecord[tags][]" multiple>',
            $content
        );

        self::assertStringContainsString('<option value="1">One</option>', $content);

        // Disabled renders, invisible does not.
        self::assertStringContainsString('id="fieldrecord-locked"', $content);
        self::assertStringContainsString('disabled', $content);
        self::assertStringNotContainsString('fieldrecord-secret', $content);
    }

    public function testGroupRendersItemsATemplateAndAnAddButton(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_LINKS;
        $model->links = [
            ['label' => 'Label', 'url' => 'https://example.com'],
        ];

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('data-group="links"', $content);
        self::assertStringContainsString('data-group-max="2"', $content);
        self::assertStringContainsString('data-group-sortable', $content);

        self::assertStringContainsString(
            '<input type="text" id="fieldrecord-links-0-label" class="input" name="FieldRecord[links][0][label]" value="Label" maxlength="255">',
            $content
        );

        self::assertStringContainsString('name="FieldRecord[links][0][label_de]"', $content);
        self::assertStringContainsString('name="FieldRecord[links][0][url]"', $content);

        self::assertStringContainsString('data-group-remove', $content);
        self::assertStringContainsString('sortable-handle', $content);

        // The template carries the placeholder index, lowercased in the ids.
        self::assertStringContainsString('data-group-template', $content);
        self::assertStringContainsString('name="FieldRecord[links][' . GroupField::TEMPLATE_INDEX . '][label]"', $content);
        self::assertStringContainsString('id="fieldrecord-links---index---label"', $content);

        self::assertStringContainsString('data-group-add', $content);
        self::assertStringNotContainsString('data-group-add hidden', $content);
    }

    public function testAddButtonIsHiddenAtMaxCount(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_LINKS;
        $model->links = [
            ['label' => 'One', 'url' => 'https://one.example.com'],
            ['label' => 'Two', 'url' => 'https://two.example.com'],
        ];

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('data-group-add hidden', $content);
    }

    public function testSingleGroupRendersOneItemWithoutButtons(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_META;

        $content = Fieldset::make()
            ->model($model)
            ->rows(['meta'])
            ->render();

        self::assertStringContainsString('name="FieldRecord[meta][title]"', $content);

        self::assertStringNotContainsString('data-group-template', $content);
        self::assertStringNotContainsString('data-group-add', $content);
        self::assertStringNotContainsString('data-group-remove', $content);
    }

    public function testTypeSelectReloadsOnlyWhenTheTypesRenderDifferentFields(): void
    {
        $content = ActiveForm::make()
            ->model($this->createRecord())
            ->rows([TypeSelectField::make()->property('type')])
            ->render();

        self::assertStringContainsString('name="FieldRecord[type]"', $content);
        self::assertStringContainsString('hx-trigger="change[this.selectedOptions[0].dataset.fingerprint !== this.dataset.fingerprint]"', $content);
        self::assertStringContainsString('hx-headers=\'{"X-Form-Reload":"1"}\'', $content);
        self::assertMatchesRegularExpression('/<select[^>]* data-fingerprint="[0-9a-f]{32}"/', $content);
        self::assertMatchesRegularExpression('/<option value="2" data-fingerprint="[0-9a-f]{32}">Links<\/option>/', $content);

        $model = UniformTypeRecord::create();
        $model->type = UniformTypeRecord::TYPE_DEFAULT;

        $content = ActiveForm::make()
            ->model($model)
            ->rows([TypeSelectField::make()->property('type')])
            ->render();

        self::assertMatchesRegularExpression('/<select[^>]*>/', $content);
        preg_match('/<select[^>]*>/', $content, $select);

        self::assertStringContainsString('name="UniformTypeRecord[type]"', $select[0]);
        self::assertStringNotContainsString('data-fingerprint', $content);
        self::assertStringNotContainsString('hx-', $select[0]);
    }

    protected function createRecord(): FieldRecord
    {
        $model = FieldRecord::create();
        $model->type = FieldRecord::TYPE_DEFAULT;

        return $model;
    }
}

/**
 * @property int $id
 * @property int $type
 * @property array|null $custom_attributes
 * @property string|null $subtitle
 * @property string|null $subtitle_de
 * @property bool|null $featured
 * @property int|null $layout
 * @property list<int>|null $tags
 * @property string|null $locked
 * @property string|null $secret
 * @property array|null $links
 * @property array|null $meta
 */
class FieldRecord extends ActiveRecord implements
    CustomAttributeInterface,
    TranslationInterface,
    TypeAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use TranslationTrait;
    use TypeAttributeTrait;

    final public const int TYPE_LINKS = 2;
    final public const int TYPE_META = 3;

    #[Override]
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEFAULT => [
                'name' => 'Default',
                'customAttributes' => fn (): array => [
                    TextCustomAttribute::make('subtitle')
                        ->max(20)
                        ->translatable(),
                    BooleanCustomAttribute::make('featured'),
                    SelectCustomAttribute::make('layout')
                        ->options([1 => 'One', 2 => 'Two']),
                    SelectCustomAttribute::make('tags')
                        ->options([1 => 'One', 2 => 'Two'])
                        ->multiple(),
                    TextCustomAttribute::make('locked')
                        ->disabled(),
                    TextCustomAttribute::make('secret')
                        ->visible(false),
                ],
            ],
            self::TYPE_LINKS => [
                'name' => 'Links',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->maxCount(2)
                        ->attributes([
                            TextCustomAttribute::make('label')->translatable(),
                            UrlCustomAttribute::make('url')->required(),
                        ]),
                ],
            ],
            self::TYPE_META => [
                'name' => 'Meta',
                'customAttributes' => fn (): array => [
                    GroupCustomAttribute::make('meta')
                        ->attributes([TextCustomAttribute::make('title')]),
                ],
            ],
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [
                ['type'],
                DynamicRangeValidator::class,
            ],
        ];
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    /**
     * @return I18nActiveQuery<static>
     */
    #[Override]
    public static function find(): I18nActiveQuery
    {
        return Yii::createObject(I18nActiveQuery::class, [static::class]);
    }

    #[Override]
    public static function tableName(): string
    {
        return 'field_test';
    }
}

/**
 * Two types sharing one definition list, so the type select has nothing to reload for.
 */
class UniformTypeRecord extends FieldRecord
{
    #[Override]
    public static function getTypes(): array
    {
        $customAttributes = fn (): array => [TextCustomAttribute::make('subtitle')];

        return [
            self::TYPE_DEFAULT => [
                'name' => 'Default',
                'customAttributes' => $customAttributes,
            ],
            self::TYPE_LINKS => [
                'name' => 'Other',
                'customAttributes' => $customAttributes,
            ],
        ];
    }
}
