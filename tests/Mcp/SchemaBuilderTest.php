<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Mcp\SchemaBuilder;
use Hirtz\Skeleton\Models\CustomAttributes\BooleanCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\DateCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\DateTimeCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\EmailCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HexColorCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\HtmlCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\NumberCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Hirtz\Skeleton\Validators\HexColorValidator;
use Hirtz\Skeleton\Validators\HtmlValidator;
use Hirtz\Skeleton\Validators\RelationValidator;
use Override;
use Yii;

class SchemaBuilderTest extends TestCase
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
            ->createTable(SchemaRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'status' => 'integer not null default 1',
                'parent_id' => 'integer null',
                'position' => 'integer not null default 0',
                'entry_count' => 'integer not null default 0',
                'name' => 'string null',
                'email' => 'string null',
                'website' => 'string null',
                'color' => 'string null',
                'body' => 'text null',
                'amount' => 'integer null',
                'price' => 'decimal(10,2) null',
                'is_featured' => 'boolean not null default 0',
                'published_at' => 'datetime null',
                'day' => 'date null',
                'size' => 'string null',
                'note' => 'string null',
                'loose' => 'integer null',
                'custom_attributes' => 'json null',
                'updated_by_user_id' => 'integer null',
                'updated_at' => 'datetime null',
                'created_at' => 'datetime null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(SchemaRecord::tableName())
            ->execute();
    }

    public function testMaintainedAttributesAreNotWritable(): void
    {
        $properties = $this->getProperties();

        foreach (['id', 'type', 'position', 'entry_count', 'created_at', 'updated_at', 'updated_by_user_id', 'custom_attributes', 'loadedAt'] as $name) {
            self::assertArrayNotHasKey($name, $properties);
        }
    }

    public function testStringValidatorsMapToStrings(): void
    {
        $properties = $this->getProperties();

        self::assertSame(['title' => 'Näme Åsa', 'description' => 'Ein Hinweis для 東京 🎉', 'type' => 'string', 'maxLength' => 100], $properties['name']);
        self::assertSame(['title' => 'Näme Åsa (DE)', 'description' => 'Ein Hinweis для 東京 🎉', 'type' => 'string', 'maxLength' => 100], $properties['name_de']);
        self::assertSame(['title' => 'Email', 'type' => 'string', 'format' => 'email'], $properties['email']);
        self::assertSame(['title' => 'Website', 'type' => 'string', 'format' => 'uri'], $properties['website']);
        self::assertSame(['title' => 'Color', 'type' => 'string', 'pattern' => '^#(?:[0-9a-fA-F]{3}){1,2}$'], $properties['color']);
        self::assertSame(['title' => 'Body', 'description' => 'Purified to the tags a[href|rel|target|title], br, li, ol, p, span, strong, ul.', 'type' => 'string', 'contentMediaType' => 'text/html'], $properties['body']);
    }

    public function testNumberValidatorsMapToNumbers(): void
    {
        $properties = $this->getProperties();

        self::assertSame(['title' => 'Amount', 'type' => 'integer', 'minimum' => 0, 'maximum' => 10], $properties['amount']);
        self::assertSame(['title' => 'Price', 'type' => 'number', 'minimum' => 0.5], $properties['price']);
        self::assertSame(['title' => 'Is Featured', 'type' => 'boolean'], $properties['is_featured']);
    }

    public function testRangeValidatorsMapToEnums(): void
    {
        $properties = $this->getProperties();

        self::assertSame(['title' => 'Size', 'enum' => ['s', 'm', 'l']], $properties['size']);
        self::assertSame('integer', $properties['status']['type'] ?? null);
        self::assertEqualsCanonicalizing(array_keys(SchemaRecord::getStatusDefinitions()), $properties['status']['enum'] ?? null);
        self::assertStringStartsWith('Values: ', $properties['status']['description'] ?? '');

        foreach (SchemaRecord::getStatusDefinitions() as $value => $definition) {
            self::assertStringContainsString("$value: {$definition->getName()}", $properties['status']['description'] ?? '');
        }
    }

    public function testAColumnTypesAnAttributeNoValidatorTypes(): void
    {
        $properties = $this->getProperties();

        self::assertSame(['title' => 'Loose', 'type' => ['integer', 'null']], $properties['loose']);
        self::assertSame(['title' => 'Free'], $properties['free']);
    }

    public function testASentenceWithAPlaceholderIsLeftOutOfTheHint(): void
    {
        self::assertSame(['title' => 'Note', 'description' => 'Kurz und knapp.', 'type' => ['string', 'null']], $this->getProperties()['note']);
    }

    public function testDatesMapToFormats(): void
    {
        $properties = $this->getProperties();

        self::assertSame('date-time', $properties['published_at']['format'] ?? null);
        self::assertStringContainsString(Yii::$app->getTimeZone(), $properties['published_at']['$comment'] ?? '');
        self::assertSame(['title' => 'Day', 'type' => 'string', 'format' => 'date'], $properties['day']);
    }

    public function testARelationIsANullableId(): void
    {
        self::assertSame(['integer', 'null'], $this->getProperties()['parent_id']['type'] ?? null);
    }

    public function testAnUnmappedAttributeKeepsItsLabel(): void
    {
        self::assertSame(['title' => 'Free'], $this->getProperties()['free']);
    }

    public function testRequiredAttributesAreListed(): void
    {
        $schema = $this->build();

        self::assertSame(['headline', 'name', 'name_de'], $schema['required'] ?? null);
        self::assertFalse($schema['additionalProperties'] ?? null);
    }

    public function testCustomAttributesMapByClass(): void
    {
        $properties = $this->getProperties();

        self::assertSame(['title' => 'Headline', 'type' => 'string', 'maxLength' => 20], $properties['headline']);
        self::assertSame(['title' => 'Subtitle (DE)', 'type' => 'string', 'maxLength' => 20], $properties['subtitle_de']);
        self::assertSame(['title' => 'Flag', 'type' => 'boolean'], $properties['flag']);
        self::assertSame(['title' => 'Count', 'type' => 'integer', 'maximum' => 10], $properties['count']);
        self::assertSame(['title' => 'Ratio', 'type' => 'number'], $properties['ratio']);
        self::assertSame(['title' => 'Layout', 'enum' => [1, 2]], $properties['layout']);
        self::assertSame(['title' => 'Tags', 'type' => 'array', 'items' => ['enum' => ['a', 'b']]], $properties['tags']);
        self::assertSame(['title' => 'Intro', 'description' => 'Purified to the tags a[href|rel|target|title], br, li, ol, p, span, strong, ul.', 'type' => 'string', 'contentMediaType' => 'text/html', 'maxLength' => 65535], $properties['intro']);
        self::assertSame(['title' => 'Contact', 'type' => 'string', 'maxLength' => 255, 'format' => 'email'], $properties['contact']);
        self::assertSame('string', $properties['link']['type'] ?? null);
        self::assertSame('^#(?:[0-9a-fA-F]{3}){1,2}$', $properties['tint']['pattern'] ?? null);
        self::assertSame('date', $properties['birthday']['format'] ?? null);
        self::assertSame('date-time', $properties['starts_at']['format'] ?? null);
    }

    public function testAnUploadIsAReadOnlyUrl(): void
    {
        self::assertSame(
            ['title' => 'Attachment', 'type' => ['string', 'null'], 'format' => 'uri', 'readOnly' => true],
            $this->getProperties()['attachment'],
        );
    }

    public function testADisabledAttributeIsReadOnlyAndKeepsItsType(): void
    {
        self::assertSame(
            ['title' => 'Score', 'type' => 'integer', 'minimum' => 0, 'readOnly' => true],
            $this->getProperties()['score'],
        );
    }

    public function testReadOnlyAttributesAreNotWritable(): void
    {
        $builder = new SchemaBuilder(Resource::make('schema', SchemaRecord::class));
        $record = SchemaRecord::instantiateByType(SchemaRecord::TYPE_DEFAULT);

        self::assertSame(['score', 'attachment'], $builder->getReadOnlyAttributes($record));
        self::assertNotContains('score', $builder->getWritableAttributes($record));
        self::assertNotContains('attachment', $builder->getWritableAttributes($record));
    }

    public function testAGroupIsAnArrayOfObjects(): void
    {
        $links = $this->getProperties()['links'];

        self::assertSame('array', $links['type'] ?? null);
        self::assertSame(5, $links['maxItems'] ?? null);
        self::assertSame(['label', 'label_de', 'url'], array_keys($links['items']['properties'] ?? []));
        self::assertSame(['url'], $links['items']['required'] ?? null);
    }

    public function testASingleGroupIsAnObject(): void
    {
        $address = $this->getProperties()['address'];

        self::assertSame('object', $address['type'] ?? null);
        self::assertSame(['street'], array_keys($address['properties'] ?? []));
    }

    public function testHiddenFieldsAreLeftOut(): void
    {
        $properties = $this->getProperties();
        self::assertArrayNotHasKey('secret', $properties);

        $properties = $this->getProperties(SchemaRecord::TYPE_PLAIN);

        self::assertArrayNotHasKey('email', $properties);
        self::assertArrayNotHasKey('headline', $properties);
        self::assertArrayHasKey('website', $properties);
    }

    public function testTheResourceNarrowsTheAttributes(): void
    {
        $resource = Resource::make('schema', SchemaRecord::class)
            ->exceptAttributes('email', 'website')
            ->attributes(fn (SchemaRecord $record, array $attributes): array => array_values(array_diff($attributes, ['color'])));

        $record = SchemaRecord::instantiateByType(null);
        $record->loadDefaultValues();

        $properties = (new SchemaBuilder($resource))->build($record)['properties'] ?? [];

        self::assertIsArray($properties);
        self::assertArrayNotHasKey('email', $properties);
        self::assertArrayNotHasKey('website', $properties);
        self::assertArrayNotHasKey('color', $properties);
        self::assertArrayHasKey('name', $properties);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getProperties(?int $type = null): array
    {
        /** @var array<string, array<string, mixed>> */
        return $this->build($type)['properties'] ?? self::fail('No properties');
    }

    /**
     * @return array<string, mixed>
     */
    private function build(?int $type = null): array
    {
        $record = SchemaRecord::instantiateByType($type);
        $record->loadDefaultValues();

        return (new SchemaBuilder(Resource::make('schema', SchemaRecord::class)))->build($record);
    }
}

/**
 * @property int $id
 * @property int|null $parent_id
 * @property int $position
 * @property int $entry_count
 * @property string|null $name
 * @property string|null $email
 * @property string|null $website
 * @property string|null $color
 * @property string|null $body
 * @property int|null $amount
 * @property string|null $price
 * @property int $is_featured
 * @property DateTime|null $published_at
 * @property Date|null $day
 * @property string|null $size
 * @property string|null $note
 * @property int|null $loose
 * @property int|null $updated_by_user_id
 * @property DateTime|null $updated_at
 * @property DateTime|null $created_at
 *
 * @property-read SchemaRecord|null $parent
 */
class SchemaRecord extends ActiveRecord implements
    AdminModelInterface,
    CustomAttributeInterface,
    StatusAttributeInterface,
    TranslationInterface,
    TypeAttributeInterface,
    VisibleAttributeInterface
{
    use AdminModelTrait;
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use StatusAttributeTrait;
    use TranslationTrait;
    use TypeAttributeTrait;
    use VisibleAttributeTrait;

    final public const int TYPE_PLAIN = 2;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes(fn (): array => [
                    TextCustomAttribute::make('headline')->max(20)->required(),
                    TextCustomAttribute::make('subtitle')->max(20)->translatable(),
                    TextCustomAttribute::make('secret')->visible(false),
                    BooleanCustomAttribute::make('flag'),
                    NumberCustomAttribute::make('count')->max(10),
                    NumberCustomAttribute::make('ratio')->integerOnly(false),
                    NumberCustomAttribute::make('score')->min(0)->disabled(),
                    SelectCustomAttribute::make('layout')->options([1 => 'Eins', 2 => 'Zwei']),
                    SelectCustomAttribute::make('tags')->options(['a' => 'Å', 'b' => 'ą'])->multiple(),
                    HtmlCustomAttribute::make('intro'),
                    EmailCustomAttribute::make('contact'),
                    UrlCustomAttribute::make('link'),
                    HexColorCustomAttribute::make('tint'),
                    DateCustomAttribute::make('birthday'),
                    DateTimeCustomAttribute::make('starts_at'),
                    UploadCustomAttribute::make('attachment')->extensions(['pdf']),
                    GroupCustomAttribute::make('links')
                        ->multiple()
                        ->maxCount(5)
                        ->attributes([
                            TextCustomAttribute::make('label')->translatable(),
                            UrlCustomAttribute::make('url')->required(),
                        ]),
                    GroupCustomAttribute::make('address')
                        ->attributes([TextCustomAttribute::make('street')]),
                ]),
            Type::make(self::TYPE_PLAIN)
                ->name('Plain')
                ->hiddenFields('email'),
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            [['type', 'status'], DynamicRangeValidator::class],
            ...$this->getI18nRules([
                [['name'], 'required'],
                [['name'], 'string', 'max' => 100],
            ]),
            [['email'], 'email'],
            [['website'], 'url'],
            [['color'], HexColorValidator::class],
            [['body'], HtmlValidator::class],
            [['amount'], 'integer', 'min' => 0, 'max' => 10],
            [['price'], 'number', 'min' => 0.5],
            [['is_featured'], 'boolean'],
            [['published_at'], DateTimeValidator::class],
            [['day'], DateTimeValidator::class, 'type' => AttributeTypecastBehavior::TYPE_DATE],
            [['size'], 'in', 'range' => ['s', 'm', 'l']],
            [['parent_id'], RelationValidator::class],
            [['free', 'note', 'loose', 'position', 'entry_count', 'updated_by_user_id', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public string $free = '';

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'name' => 'Näme Åsa',
        ];
    }

    #[Override]
    public function attributeHints(): array
    {
        return [
            ...parent::attributeHints(),
            'name' => '<b>Ein Hinweis</b> для 東京 🎉',
            'note' => 'Kurz und knapp. Jetzt: {count} Zeichen.',
        ];
    }

    /**
     * @return ActiveQuery<SchemaRecord>
     */
    public function getParent(): ActiveQuery
    {
        /** @var ActiveQuery<SchemaRecord> $query */
        $query = $this->hasOne(self::class, ['id' => 'parent_id']);
        return $query;
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public function getAdminRoute(): array|false
    {
        return false;
    }

    #[Override]
    public function getPermissionName(): string
    {
        return 'redirect';
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
        return 'mcp_schema_record';
    }
}
