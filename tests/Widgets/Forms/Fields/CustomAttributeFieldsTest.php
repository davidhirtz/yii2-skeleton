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
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
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
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(FieldRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(FieldRecord::tableName())
            ->execute();
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
            '<input type="text" id="fieldrecord-links-0-label" class="input" name="FieldRecord[links][0][label]" value="Label" maxlength="255" data-group-title-input>',
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

        self::assertStringNotContainsString('hidden', $this->findAddButton($content));
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

        self::assertStringContainsString('hidden', $this->findAddButton($content));
    }

    public function testMinCountRendersItsRowsUpFront(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_REQUIRED_LINKS;

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('data-group-min="2"', $content);
        self::assertStringContainsString('name="FieldRecord[links][0][label]"', $content);
        self::assertStringContainsString('name="FieldRecord[links][1][label]"', $content);
        self::assertStringNotContainsString('name="FieldRecord[links][2][label]"', $content);
    }

    public function testMinCountOnlyTopsUpTheStoredRows(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_REQUIRED_LINKS;
        $model->links = [['label' => 'One']];

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('name="FieldRecord[links][0][label]" value="One"', $content);
        self::assertStringContainsString('name="FieldRecord[links][1][label]"', $content);
        self::assertStringNotContainsString('name="FieldRecord[links][2][label]"', $content);
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

    public function testTheGroupLabelsTheRowItRendersInto(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_LINKS;
        $model->links = [['label' => 'Label', 'url' => 'https://example.com']];

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('<div class="form-group form-row" data-id="fieldrecord-links">', $content);

        // The label points at the first field of the first row; the group as a whole is named by the container,
        // since a `<label for>` labels one control.
        // A collapsed row hides its fields and a `<label for>` naming a hidden control does nothing, so the
        // label points at the toggle that opens them.
        self::assertStringContainsString(
            '<label id="fieldrecord-links" class="label" for="fieldrecord-links-0-toggle">Links</label>',
            $content
        );

        self::assertStringContainsString(
            '<fieldset class="custom-attribute-group-fieldset" aria-labelledby="fieldrecord-links">',
            $content
        );
    }

    /**
     * The only ids a group with no row carries are its template's, which are the placeholder and are not in the
     * document at all.
     */
    public function testAGroupWithNoRowHasNoFieldToPointAt(): void
    {
        $content = $this->renderGroup(FieldRecord::TYPE_LINKS);

        self::assertStringContainsString(
            '<div class="form-label"><div id="fieldrecord-links" class="label">Links</div></div>',
            $content
        );

        self::assertStringContainsString('aria-labelledby="fieldrecord-links"', $content);

        // The template's own labels are there, pointing at the placeholder; nothing points at a row.
        self::assertStringNotContainsString('for="fieldrecord-links-0', $content);
    }

    public function testASingleFieldGroupDropsTheFieldsOwnLabel(): void
    {
        $content = $this->renderGroup(FieldRecord::TYPE_REQUIRED_LINKS);

        self::assertStringContainsString('custom-attribute-group custom-attribute-group-single', $content);
        self::assertStringContainsString('class="custom-attribute-group-item form-action"', $content);

        self::assertStringContainsString('name="FieldRecord[links][0][label]"', $content);

        // The field's own label is gone; the group's points at it.
        self::assertStringNotContainsString('<label class="label" for="fieldrecord-links-0-label">', $content);
        self::assertStringContainsString('id="fieldrecord-links" class="label" for="fieldrecord-links-0-label"', $content);
    }

    /**
     * What decides the layout is the field count, not the attribute count: a translatable attribute is a field
     * per configured language.
     */
    public function testATranslatedAttributeIsMoreThanOneField(): void
    {
        $content = $this->renderGroup(FieldRecord::TYPE_TRANSLATED_LINKS);

        self::assertStringNotContainsString('custom-attribute-group-single', $content);
        self::assertStringContainsString('for="fieldrecord-links---index---label-de"', $content);
    }

    public function testTheCountTheGroupIsBoundByIsShownBesideTheAddButton(): void
    {
        self::assertStringContainsString(
            '<div class="form-hint">Between 1 and 3 entries</div>',
            $this->renderGroup(FieldRecord::TYPE_BOUNDED_LINKS)
        );

        self::assertStringContainsString(
            '<div class="form-hint">At most 2 entries</div>',
            $this->renderGroup(FieldRecord::TYPE_LINKS)
        );

        self::assertStringContainsString(
            '<div class="form-hint">At least 2 entries</div>',
            $this->renderGroup(FieldRecord::TYPE_REQUIRED_LINKS)
        );

        $content = $this->renderGroup(FieldRecord::TYPE_TRANSLATED_LINKS);

        self::assertStringContainsString('data-group-add', $content);
        self::assertStringNotContainsString('form-hint', $content);
    }

    public function testARowOfSeveralFieldsIsPreviewedByItsTitleAndExpands(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_LINKS;
        $model->links = [
            ['label' => 'One', 'url' => 'https://one.example.com'],
            ['url' => 'https://two.example.com'],
        ];

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        // The title defaults to the first attribute the group declares, and the script is handed the pattern a
        // row with nothing typed into it falls back to.
        self::assertStringContainsString('data-group-position="#' . GroupField::POSITION_PLACEHOLDER . '"', $content);
        self::assertStringContainsString('name="FieldRecord[links][0][label]" value="One" maxlength="255" data-group-title-input', $content);

        self::assertStringContainsString(
            '<div class="custom-attribute-group-item-title" data-group-title>One</div>',
            $content
        );

        // The second row has no label, so it is its number — and says so, since the script rewrites only those.
        self::assertStringContainsString(
            '<div class="custom-attribute-group-item-title" data-group-title data-group-title-position>#2</div>',
            $content
        );

        self::assertStringContainsString(
            '<button type="button" id="fieldrecord-links-0-toggle" class="btn btn-link custom-attribute-group-item-toggle" data-group-toggle aria-controls="fieldrecord-links-0-fields" aria-expanded="false">',
            $content
        );

        self::assertStringContainsString(
            '<div id="fieldrecord-links-0-fields" class="custom-attribute-group-item-body" data-group-body hidden>',
            $content
        );
    }

    /**
     * A control the browser cannot focus blocks the submit with nothing on screen to say why, so the row a
     * failed validation left an error on arrives open.
     */
    public function testARowWithAnErrorIsRenderedOpen(): void
    {
        $model = $this->createRecord();
        $model->type = FieldRecord::TYPE_LINKS;
        $model->links = [
            ['label' => 'One', 'url' => 'https://one.example.com'],
            ['label' => 'Two', 'url' => ''],
        ];

        self::assertFalse($model->validate());

        $content = Fieldset::make()
            ->model($model)
            ->rows(['links'])
            ->render();

        self::assertStringContainsString('aria-controls="fieldrecord-links-0-fields" aria-expanded="false"', $content);
        self::assertStringContainsString('aria-controls="fieldrecord-links-1-fields" aria-expanded="true"', $content);

        self::assertStringContainsString(
            '<div id="fieldrecord-links-1-fields" class="custom-attribute-group-item-body" data-group-body>',
            $content
        );
    }

    /**
     * There is nothing to fold away: a group that holds one row holds it open, and a row of one field is the
     * line it would collapse to.
     */
    public function testOnlyARepeatedRowOfSeveralFieldsCollapses(): void
    {
        self::assertStringNotContainsString('data-group-toggle', $this->renderGroup(FieldRecord::TYPE_META));
        self::assertStringNotContainsString('data-group-toggle', $this->renderGroup(FieldRecord::TYPE_REQUIRED_LINKS));
        self::assertStringContainsString('data-group-toggle', $this->renderGroup(FieldRecord::TYPE_TRANSLATED_LINKS));
    }

    /**
     * The button is icon-only, so it carries an `aria-label` and a tooltip between `data-group-add` and the
     * `hidden` a maximum count adds — asserting on the two next to each other pinned the attribute order.
     */
    protected function findAddButton(string $content): string
    {
        preg_match('/<button[^>]*data-group-add[^>]*>/', $content, $matches);

        return $matches[0] ?? self::fail('The group renders no add button.');
    }

    protected function renderGroup(int $type): string
    {
        $model = $this->createRecord();
        $model->type = $type;

        return Fieldset::make()
            ->model($model)
            ->rows(['links', 'meta'])
            ->render();
    }

    /**
     * Every type change reloads, whatever the types have in common: what a type decides reaches past its custom
     * attributes — hidden fields, a field another bundle contributes, a panel outside the form — and none of that
     * is visible from here. See monorepo issue #118.
     */
    public function testTypeSelectReloadsThePage(): void
    {
        $select = $this->renderTypeSelect($this->createRecord());

        self::assertStringContainsString('name="FieldRecord[type]"', $select);
        self::assertStringContainsString('hx-trigger="change"', $select);
        self::assertStringContainsString('hx-include="closest form"', $select);
        self::assertStringContainsString('hx-select="#wrap"', $select);
        self::assertStringContainsString('hx-target="#wrap"', $select);
        self::assertStringContainsString('hx-swap="outerHTML"', $select);
        self::assertStringContainsString('hx-headers=\'{"X-Form-Reload":"1"}\'', $select);
    }

    public function testTypeSelectReloadsForTypesSharingTheirCustomAttributes(): void
    {
        $model = UniformTypeRecord::create();
        $model->type = UniformTypeRecord::TYPE_DEFAULT;

        $select = $this->renderTypeSelect($model);

        self::assertStringContainsString('name="UniformTypeRecord[type]"', $select);
        self::assertStringContainsString('hx-post', $select);
    }

    public function testASingleTypeHasNothingToReloadFor(): void
    {
        $model = SingleTypeRecord::create();
        $model->type = SingleTypeRecord::TYPE_DEFAULT;

        $select = $this->renderTypeSelect($model);

        self::assertStringContainsString('name="SingleTypeRecord[type]"', $select);
        self::assertStringNotContainsString('hx-', $select);
    }

    /**
     * A hidden field is neither rendered nor safe, so the value a record holds under a type that hides it is kept
     * rather than overwritten by whatever the form did not post.
     */
    public function testATypeHidingACustomAttributeDropsItsField(): void
    {
        $model = HiddenFieldRecord::create();
        $model->type = HiddenFieldRecord::TYPE_WITHOUT_SUBTITLE;

        $content = Fieldset::make()
            ->model($model)
            ->rows(['featured'])
            ->render();

        self::assertStringContainsString('name="HiddenFieldRecord[featured]"', $content);
        self::assertFalse($model->isAttributeSafe('subtitle'));

        $content = Fieldset::make()
            ->model($model)
            ->rows(['subtitle'])
            ->render();

        self::assertSame('', $content);
    }

    protected function renderTypeSelect(FieldRecord $model): string
    {
        $content = ActiveForm::make()
            ->model($model)
            ->rows([TypeSelectField::make()->property('type')])
            ->render();

        preg_match('/<select[^>]*>/', $content, $select);

        return $select[0] ?? self::fail('The form renders no select.');
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
 * @property array<string, mixed>|null $custom_attributes
 * @property string|null $subtitle
 * @property string|null $subtitle_de
 * @property bool|null $featured
 * @property int|null $layout
 * @property list<int>|null $tags
 * @property string|null $locked
 * @property string|null $secret
 * @property list<array<string, string>>|null $links
 * @property array<string, mixed>|null $meta
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
    final public const int TYPE_REQUIRED_LINKS = 4;
    final public const int TYPE_BOUNDED_LINKS = 5;
    final public const int TYPE_TRANSLATED_LINKS = 6;

    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes(fn (): array => [
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
                ]),
            Type::make(self::TYPE_LINKS)
                ->name('Links')
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->maxCount(2)
                        ->attributes([
                            TextCustomAttribute::make('label')->translatable(),
                            UrlCustomAttribute::make('url')->required(),
                        ]),
                ]),
            Type::make(self::TYPE_META)
                ->name('Meta')
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('meta')
                        ->attributes([TextCustomAttribute::make('title')]),
                ]),
            Type::make(self::TYPE_REQUIRED_LINKS)
                ->name('Required links')
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->minCount(2)
                        ->attributes([TextCustomAttribute::make('label')]),
                ]),
            Type::make(self::TYPE_BOUNDED_LINKS)
                ->name('Bounded links')
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->minCount(1)
                        ->maxCount(3)
                        ->attributes([TextCustomAttribute::make('label')]),
                ]),
            Type::make(self::TYPE_TRANSLATED_LINKS)
                ->name('Translated links')
                ->customAttributes(fn (): array => [
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->attributes([TextCustomAttribute::make('label')->translatable()]),
                ]),
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
    public function getTypes(): array
    {
        $customAttributes = fn (): array => [TextCustomAttribute::make('subtitle')];

        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes($customAttributes),
            Type::make(self::TYPE_LINKS)
                ->name('Other')
                ->customAttributes($customAttributes),
        ];
    }
}

/**
 * A type carrying nothing to choose between, which is the one case the select has no reload to offer.
 */
class SingleTypeRecord extends FieldRecord
{
    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes(fn (): array => [TextCustomAttribute::make('subtitle')]),
        ];
    }
}

class HiddenFieldRecord extends FieldRecord implements VisibleAttributeInterface
{
    use VisibleAttributeTrait;

    final public const int TYPE_WITHOUT_SUBTITLE = 5;

    #[Override]
    public function getTypes(): array
    {
        $customAttributes = fn (): array => [
            TextCustomAttribute::make('subtitle'),
            BooleanCustomAttribute::make('featured'),
        ];

        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes($customAttributes),
            Type::make(self::TYPE_WITHOUT_SUBTITLE)
                ->name('Without subtitle')
                ->customAttributes($customAttributes)
                ->hiddenFields('subtitle'),
        ];
    }
}
