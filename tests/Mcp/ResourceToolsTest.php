<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Behaviors\TimestampBehavior;
use Hirtz\Skeleton\Behaviors\TrailBehavior;
use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Mcp\Tools\CreateRecordTool;
use Hirtz\Skeleton\Mcp\Tools\FindRecordsTool;
use Hirtz\Skeleton\Mcp\Tools\FindTrailTool;
use Hirtz\Skeleton\Mcp\Tools\GetRecordTool;
use Hirtz\Skeleton\Mcp\Tools\ReorderTool;
use Hirtz\Skeleton\Mcp\Tools\SetStatusTool;
use Hirtz\Skeleton\Mcp\Tools\ToolInterface;
use Hirtz\Skeleton\Mcp\Tools\UpdateRecordTool;
use Hirtz\Skeleton\Models\Actions\ReorderActiveRecords;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\VisibleAttributeInterface;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Translation;
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
use Hirtz\Skeleton\Upload\Upload;
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
                'creatable' => true,
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
        self::assertSame(['teaser', 'teaser_de', 'status', 'name', 'name_de', 'attachment'], array_keys($result['attributes']['properties'] ?? []));
        self::assertTrue($result['attributes']['properties']['attachment']['readOnly'] ?? false);
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

    public function testGetRecordAnswersAFileAsItsUrl(): void
    {
        $record = $this->insertRecord('Ärger', ['attachment' => 'Prüfung.pdf']);
        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id]);

        self::assertSame(
            Url::to(Upload::getComponent()->getUrl($record, 'attachment', 'Prüfung.pdf'), true),
            $result['attributes']['attachment'] ?? null,
        );

        $record = $this->insertRecord('Leer');
        $result = $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id]);

        self::assertArrayHasKey('attachment', $result['attributes'] ?? []);
        self::assertNull($result['attributes']['attachment']);
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

    public function testCreateRecord(): void
    {
        $result = $this->callTool('create_record', [
            'resource' => 'scratch',
            'attributes' => ['name' => 'Åsa ąb', 'name_de' => 'Mu' . "\u{308}" . 'ller 🎉', 'teaser_de' => 'Привет 東京'],
        ]);

        $id = $result['id'] ?? self::fail('No id');
        $record = ToolRecord::findOne($id);

        self::assertNotNull($record);
        self::assertSame('Mu' . "\u{308}" . 'ller 🎉', $record->getI18nAttribute('name', 'de'));
        self::assertSame('Привет 東京', $record->getAttribute('teaser_de'));
        self::assertSame(ToolRecord::TYPE_DEFAULT, $record->type);

        self::assertSame(['Mu' . "\u{308}" . 'ller 🎉'], Translation::find()
            ->select('value')
            ->where(['model_class' => ToolRecord::class, 'model_id' => $id, 'attribute' => 'name', 'language' => 'de'])
            ->column());

        $trail = Trail::find()->where(['model_class' => ToolRecord::class, 'model_id' => (string)$id, 'type' => Trail::TYPE_CREATE])->one();
        self::assertSame($this->getUserFromFixture('owner')->id, $trail?->user_id);
    }

    public function testCreateRecordOfAType(): void
    {
        $result = $this->callTool('create_record', [
            'resource' => 'scratch',
            'type' => ToolRecord::TYPE_OTHER,
            'attributes' => ['name' => 'Other', 'name_de' => 'Andere'],
        ]);

        self::assertSame('Ånother', $result['type_name'] ?? null);

        $result = $this->callTool('create_record', ['resource' => 'scratch', 'type' => 9, 'attributes' => []], true);
        self::assertSame('Unknown type 9 for scratch.', $result['error'] ?? null);
    }

    public function testCreateRecordAnswersTheValidationErrors(): void
    {
        $result = $this->callTool('create_record', ['resource' => 'scratch', 'attributes' => ['name' => str_repeat('ą', 51)]], true);

        self::assertSame('The record was not saved.', $result['error'] ?? null);
        self::assertEqualsCanonicalizing(['name', 'name_de'], array_keys($result['errors'] ?? []));
        self::assertSame(0, (int)ToolRecord::find()->count());
    }

    public function testAnAttributeThatIsNotWritableIsRefused(): void
    {
        $result = $this->callTool('create_record', ['resource' => 'scratch', 'attributes' => ['name' => 'A', 'position' => 3, 'type' => 2]], true);

        self::assertSame('Not writable: position, type. See describe_resource.', $result['error'] ?? null);
        self::assertContains('name_de', $result['writable'] ?? []);
    }

    public function testUpdateRecord(): void
    {
        $record = $this->insertRecord('Ärger');
        $result = $this->callTool('update_record', ['resource' => 'scratch', 'id' => $record->id, 'attributes' => ['name_de' => 'Ąb']]);

        self::assertSame('Ąb', $result['attributes']['name_de'] ?? null);

        $record->refresh();
        self::assertSame('Ąb', $record->getI18nAttribute('name', 'de'));
        self::assertSame('Ärger', $record->name);
    }

    public function testAStaleUpdateIsRefused(): void
    {
        $record = $this->insertRecord('Ärger');

        $record->name = 'Changed by someone else';
        $record->update();

        $result = $this->callTool('update_record', [
            'resource' => 'scratch',
            'id' => $record->id,
            'attributes' => ['name' => 'Mine'],
            'updated_at' => '2000-01-01T00:00:00+00:00',
        ], true);

        self::assertArrayHasKey('loadedAt', $result['errors'] ?? []);
        self::assertIsString($result['updated_at'] ?? null);

        $record->refresh();
        self::assertSame('Changed by someone else', $record->name);

        $result = $this->callTool('update_record', [
            'resource' => 'scratch',
            'id' => $record->id,
            'attributes' => ['name' => 'Mine'],
            'updated_at' => $this->callTool('get_record', ['resource' => 'scratch', 'id' => $record->id])['attributes']['updated_at'] ?? '',
        ]);

        self::assertSame('Mine', $result['attributes']['name'] ?? null);
    }

    public function testSetStatus(): void
    {
        $record = $this->insertRecord('Ärger');
        $result = $this->callTool('set_status', ['resource' => 'scratch', 'id' => $record->id, 'status' => ToolRecord::STATUS_DISABLED]);

        self::assertSame(ToolRecord::STATUS_DISABLED, $result['status'] ?? null);

        $result = $this->callTool('set_status', ['resource' => 'scratch', 'id' => $record->id, 'status' => 42], true);
        self::assertSame('Unknown status 42, see describe_resource.', $result['error'] ?? null);
    }

    public function testAWriteToARefusedRecordLooksNotFound(): void
    {
        $record = $this->insertRecord('Ärger');
        $this->getWebUser()->setIdentity($this->getUserFromFixture('admin'));

        $result = $this->callTool('update_record', ['resource' => 'scratch', 'id' => $record->id, 'attributes' => ['name' => 'x']], true);

        self::assertSame('Resource not found: scratch', $result['error'] ?? null);
    }

    public function testAReadOnlyResourceIsNotWritten(): void
    {
        $record = $this->insertRecord('Ärger');

        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('scratch', ToolRecord::class)->readOnly(),
        ];

        $names = array_map(fn (ToolInterface $tool): string => $tool->getName(), Mcp::getComponent()->getTools());
        self::assertSame(['list_resources', 'describe_resource', 'find_records', 'get_record', 'find_trail'], $names);

        $tool = new UpdateRecordTool(Mcp::getComponent());
        $result = $tool->call(['resource' => 'scratch', 'id' => $record->id, 'attributes' => ['name' => 'x']]);

        self::assertTrue($result->isError);
        self::assertSame('Resource scratch is read-only.', $result->structuredContent['error'] ?? null);
    }

    public function testOnlyToolsOverwritingRecordsAreDestructive(): void
    {
        $mcp = Mcp::getComponent();

        self::assertTrue((new UpdateRecordTool($mcp))->isDestructive());
        self::assertTrue((new SetStatusTool($mcp))->isDestructive());
        self::assertTrue((new ReorderTool($mcp))->isDestructive());
        self::assertFalse((new CreateRecordTool($mcp))->isDestructive());
        self::assertFalse((new GetRecordTool($mcp))->isDestructive());
    }

    public function testAReadOnlyServerOffersNoWriteTool(): void
    {
        $response = Mcp::getComponent()->createServer(true)->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        $names = array_column($response['result']['tools'] ?? [], 'name');

        self::assertSame(['list_resources', 'describe_resource', 'find_records', 'get_record', 'find_trail'], $names);
    }

    public function testTheInstructionsNameTheLanguagesAndWhatBundlesAdd(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);

        $mcp = Mcp::getComponent();
        $mcp->instructions = [fn (): string => 'Einträge sind Seiten 📄.', fn (): ?string => null];

        $instructions = $mcp->getInstructions();

        self::assertStringContainsString('list_resources', $instructions);
        self::assertStringContainsString('(_de)', $instructions);
        self::assertStringContainsString('drafts', $instructions);
        self::assertStringEndsWith("\n\nEinträge sind Seiten 📄.", $instructions);

        self::assertStringContainsString('read-only', $mcp->getInstructions(true));
        self::assertStringNotContainsString('drafts', $mcp->getInstructions(true));

        $response = $mcp->createServer()->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []]);
        self::assertSame($instructions, $response['result']['instructions'] ?? null);
    }

    public function testReorder(): void
    {
        $first = $this->insertRecord('Eins');
        $second = $this->insertRecord('Zwei');

        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('scratch', ToolRecord::class)
                ->reorder(fn (?ActiveRecord $parent, array $ids): bool => (new ReorderActiveRecords(
                    ToolRecord::find()->where(['id' => $ids])->all(),
                    array_flip($ids),
                ))->run() !== false),
        ];

        $result = $this->callTool('reorder', ['resource' => 'scratch', 'ids' => [$second->id, $first->id]]);

        self::assertTrue($result['reordered'] ?? null);
        self::assertSame([$second->id, $first->id], array_map(intval(...), ToolRecord::find()->select('id')->orderBy(['position' => SORT_ASC])->column()));
    }

    public function testAResourceThatIsNotCreatableIsOnlyUpdated(): void
    {
        $record = $this->insertRecord('Ärger');

        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('scratch', ToolRecord::class)->creatable(false),
        ];

        $names = array_map(fn (ToolInterface $tool): string => $tool->getName(), Mcp::getComponent()->getTools());
        self::assertNotContains('create_record', $names);

        $result = $this->callTool('update_record', ['resource' => 'scratch', 'id' => $record->id, 'attributes' => ['name' => 'Ąb']]);
        self::assertSame('Ąb', $result['attributes']['name'] ?? null);
    }

    public function testMissingFindsEmptyColumnsTranslationsAndCustomAttributes(): void
    {
        $complete = $this->insertRecord('Eins', ['teaser' => 'Kurz', 'teaser_de' => 'Kürzer 🎉']);
        $sparse = $this->insertRecord('Zwei', ['teaser' => 'Ja']);
        Translation::deleteAll(['model_class' => $sparse->getTranslationModelClass(), 'model_id' => $sparse->id, 'attribute' => 'name']);

        $find = fn (array $missing): array => array_column((new FindRecordsTool(Mcp::getComponent()))
            ->call(['resource' => 'scratch', 'missing' => $missing])
            ->structuredContent['records'] ?? [], 'id');

        self::assertSame([$sparse->id], $find(['name_de']));
        self::assertSame([$sparse->id], $find(['teaser_de']));
        self::assertSame([], $find(['name']));
        self::assertNotContains($complete->id, $find(['name_de', 'teaser_de']));

        $result = (new FindRecordsTool(Mcp::getComponent()))->call(['resource' => 'scratch', 'missing' => ['nope']]);
        self::assertTrue($result->isError);
    }

    public function testTheTrailListsTheChangesOfAResource(): void
    {
        $record = $this->insertRecord('Ärger');
        Mcp::getComponent()->setClient('claude.ai');

        $update = (new UpdateRecordTool(Mcp::getComponent()))->call(['resource' => 'scratch', 'id' => $record->id, 'attributes' => ['name' => 'Ölung 🎉']]);
        self::assertFalse($update->isError, json_encode($update->structuredContent) ?: '');

        $trail = new Trail();
        $trail->model_class = User::class;
        $trail->model_id = (string)$this->getUserFromFixture('owner')->id;
        $trail->insert();

        $tool = new FindTrailTool(Mcp::getComponent());
        $records = $tool->call(['resource' => 'scratch', 'id' => $record->id])->structuredContent['records'] ?? [];

        self::assertSame('scratch', $records[0]['resource'] ?? null);
        self::assertSame(['Ärger', 'Ölung 🎉'], $records[0]['changes']['name'] ?? null);
        self::assertSame('claude.ai', $records[0]['client'] ?? null);
        self::assertCount(2, $records);

        $all = $tool->call(['client' => 'claude.ai'])->structuredContent;
        self::assertSame(1, $all['total'] ?? null);

        // a model no resource offers stays out of the trail an agent reads
        $ids = array_column($tool->call(['limit' => 100])->structuredContent['records'] ?? [], 'id');
        self::assertNotContains($trail->id, $ids);

        self::assertTrue($tool->call(['id' => $record->id])->isError);
    }

    public function testReorderIsOfferedOnlyForAReorderableResource(): void
    {
        $names = array_map(fn (ToolInterface $tool): string => $tool->getName(), Mcp::getComponent()->getTools());
        self::assertNotContains('reorder', $names);
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
                    UploadCustomAttribute::make('attachment')->extensions(['pdf']),
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
