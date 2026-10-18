<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\StaleSaveTrait;
use Hirtz\Skeleton\Models\Traits\StatusAttributeTrait;
use Hirtz\Skeleton\Models\Traits\TrailModelTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use Yii;

class ResourceToolsTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('scratch', ToolRecord::class),
        ];

        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));
    }

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(ToolRecord::tableName(), [
                'id' => 'pk',
                'type' => 'integer not null default 1',
                'status' => 'integer not null default 1',
                'position' => 'integer not null default 0',
                'name' => 'string null',
                'custom_attributes' => 'json null',
                'updated_at' => 'datetime null',
                'created_at' => 'datetime null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(ToolRecord::tableName())
            ->execute();
    }

    public function testListResources(): void
    {
        $result = $this->callTool('list_resources');

        self::assertSame([
            [
                'name' => 'scratch',
                'label' => 'ToolRecord',
                'has_types' => true,
                'writable' => true,
                'reorderable' => false,
            ],
        ], $result['resources'] ?? null);
    }

    public function testDescribeResource(): void
    {
        $result = $this->callTool('describe_resource', ['resource' => 'scratch', 'type' => ToolRecord::TYPE_OTHER]);

        self::assertSame(ToolRecord::TYPE_OTHER, $result['type'] ?? null);
        self::assertSame([['id' => 1, 'name' => 'Default'], ['id' => 2, 'name' => 'Ånother']], $result['types'] ?? null);
        self::assertSame(['', '_de'], array_column($result['languages'] ?? [], 'suffix'));
        self::assertSame(['status', 'name', 'name_de'], array_keys($result['attributes']['properties'] ?? []));

        $result = $this->callTool('describe_resource', ['resource' => 'scratch']);
        self::assertSame(['teaser', 'teaser_de', 'status', 'name', 'name_de'], array_keys($result['attributes']['properties'] ?? []));
    }

    public function testDescribeAnUnknownType(): void
    {
        $result = $this->callTool('describe_resource', ['resource' => 'scratch', 'type' => 9], true);
        self::assertSame('Unknown type 9 for scratch.', $result['error'] ?? null);
    }

    public function testFindRecords(): void
    {
        $first = $this->insertRecord('Ärger', ['type' => ToolRecord::TYPE_OTHER]);
        $second = $this->insertRecord('Здравствуйте 🎉');
        $this->insertRecord('東京', ['status' => ToolRecord::STATUS_DISABLED]);

        $result = $this->callTool('find_records', ['resource' => 'scratch', 'filter' => ['status' => 1], 'order' => '-id']);

        self::assertSame(2, $result['total'] ?? null);
        self::assertSame([$second->id, $first->id], array_column($result['records'] ?? [], 'id'));
        self::assertSame('Здравствуйте 🎉', $result['records'][0]['name'] ?? null);
        self::assertStringContainsString('/admin/redirect/update?id=' . $second->id, $result['records'][0]['admin_url'] ?? '');

        $result = $this->callTool('find_records', ['resource' => 'scratch', 'filter' => ['type' => [1, 2]], 'limit' => 1, 'offset' => 1, 'order' => 'id']);

        self::assertSame(3, $result['total'] ?? null);
        self::assertSame([$second->id], array_column($result['records'] ?? [], 'id'));
    }

    public function testFindRecordsRefusesAnUnknownColumn(): void
    {
        $result = $this->callTool('find_records', ['resource' => 'scratch', 'filter' => ['teaser' => 'x']], true);
        self::assertSame('Unknown column "teaser", filter takes columns only.', $result['error'] ?? null);

        $result = $this->callTool('find_records', ['resource' => 'scratch', 'order' => '-nope'], true);
        self::assertSame('Unknown column "nope" to order by.', $result['error'] ?? null);
    }

    public function testATextQueryNeedsASearchableResource(): void
    {
        $result = $this->callTool('find_records', ['resource' => 'scratch', 'query' => 'Ärger'], true);
        self::assertSame('This resource cannot be searched by text, use "filter".', $result['error'] ?? null);
    }

    public function testGetRecord(): void
    {
        $record = $this->insertRecord('Ärger', ['name_de' => 'Mu' . "\u{308}" . 'ller', 'teaser_de' => 'Ąb']);
        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id]);

        self::assertSame('scratch', $result['resource'] ?? null);
        self::assertSame($record->id, $result['id'] ?? null);
        self::assertSame('Default', $result['type_name'] ?? null);
        self::assertSame('Mu' . "\u{308}" . 'ller', $result['attributes']['name_de'] ?? null);
        self::assertSame('Ąb', $result['attributes']['teaser_de'] ?? null);
        self::assertArrayNotHasKey('custom_attributes', $result['attributes'] ?? []);
        self::assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d$/', $result['attributes']['updated_at'] ?? '');
    }

    public function testARefusedRecordLooksNotFound(): void
    {
        $record = $this->insertRecord('Ärger');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id], true);
        self::assertSame('Resource not found: scratch', $result['error'] ?? null);

        $result = $this->callTool('get_record', ['resource' => 'missing', 'id' => $record->id], true);
        self::assertSame('Resource not found: missing', $result['error'] ?? null);

        self::assertSame([], $this->callTool('list_resources')['resources'] ?? null);

        $this->assignPermission($this->getUserFromFixture('admin')->id, Redirect::AUTH_REDIRECT);
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        self::assertSame($record->id, $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id])['id'] ?? null);
    }

    public function testAMissingRecordIsAToolError(): void
    {
        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => 999999], true);
        self::assertSame('Record not found: scratch 999999', $result['error'] ?? null);
    }

    public function testTheQueryNarrowsTheResource(): void
    {
        $record = $this->insertRecord('Ärger', ['status' => ToolRecord::STATUS_DISABLED]);

        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('scratch', ToolRecord::class)
                ->query(fn (ActiveQuery $query) => $query->andWhere(['status' => ToolRecord::STATUS_ENABLED])),
        ];

        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id], true);
        self::assertStringStartsWith('Record not found', $result['error'] ?? '');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function insertRecord(string $name, array $attributes = []): ToolRecord
    {
        $record = ToolRecord::instantiateByType($attributes['type'] ?? null);
        $record->loadDefaultValues();
        $record->name = $name;
        $record->setAttribute('name_de', $name);
        $record->setAttributes($attributes, false);

        self::assertTrue($record->insert(), implode(' ', $record->getErrorSummary(true)));
        return $record;
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    protected function callTool(string $name, array $arguments = [], bool $isError = false): array
    {
        $response = Mcp::getComponent()->createServer()->handle([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ]);

        $result = $response['result'] ?? self::fail('No result: ' . json_encode($response));

        self::assertIsArray($result);
        self::assertSame($isError, $result['isError'] ?? null, json_encode($result['structuredContent'] ?? null, JSON_UNESCAPED_UNICODE) ?: '');

        /** @var array<string, mixed> */
        return $result['structuredContent'];
    }
}

/**
 * @property int $id
 * @property string|null $name
 */
class ToolRecord extends ActiveRecord implements
    AdminModelInterface,
    CustomAttributeInterface,
    StaleSaveInterface,
    StatusAttributeInterface,
    TranslationInterface,
    TypeAttributeInterface,
    VisibleAttributeInterface
{
    use AdminModelTrait;
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use StaleSaveTrait;
    use StatusAttributeTrait;
    use TrailModelTrait;
    use TranslationTrait;
    use TypeAttributeTrait;
    use VisibleAttributeTrait;

    final public const int TYPE_OTHER = 2;

    #[Override]
    public function init(): void
    {
        $this->i18nAttributes = ['name'];
        parent::init();
    }

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'TimestampBehavior' => TimestampBehavior::class,
            'TrailBehavior' => TrailBehavior::class,
        ];
    }

    #[Override]
    public function getTypes(): array
    {
        return [
            Type::make(self::TYPE_DEFAULT)
                ->name('Default')
                ->customAttributes(fn (): array => [
                    TextCustomAttribute::make('teaser')->max(30)->translatable(),
                ]),
            Type::make(self::TYPE_OTHER)
                ->name('Ånother'),
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            ...$this->getStaleSaveRules(),
            [['type', 'status'], DynamicRangeValidator::class],
            ...$this->getI18nRules([
                [['name'], 'required'],
                [['name'], 'string', 'max' => 50],
            ]),
        ];
    }

    public function getTranslationModelClass(): string
    {
        return self::class;
    }

    #[Override]
    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/redirect/update', 'id' => $this->id] : false;
    }

    #[Override]
    public function getPermissionName(): string
    {
        return Redirect::AUTH_REDIRECT;
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
        return 'mcp_tool_record';
    }
}
