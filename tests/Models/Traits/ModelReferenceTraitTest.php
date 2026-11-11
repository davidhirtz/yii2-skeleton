<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\ModelReferenceInterface;
use Hirtz\Skeleton\Models\Traits\ModelReferenceTrait;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\ModelReferenceGridTrait;
use Override;
use Yii;
use yii\data\ArrayDataProvider;

class ModelReferenceTraitTest extends TestCase
{
    use UserFixtureTrait;

    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(ModelReferenceRecord::tableName(), [
                'id' => 'pk',
                'model_class' => 'string not null',
                'model_id' => 'integer not null',
                'attribute_counts' => 'json null',
                'updated_at' => 'datetime null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(ModelReferenceRecord::tableName())
            ->execute();
    }

    public function testTheOwnersAreLoadedAndLabelTheAttributes(): void
    {
        $user = $this->getUserFromFixture('admin');

        $references = [
            $this->createReference(User::class, $user->id, ['email' => 2, 'name' => 1]),
            $this->createReference('App\\Gone', 1, ['Å.東京' => 1]),
        ];

        ModelReferenceRecord::populateModelRelations($references);

        self::assertSame($user->id, $references[0]->getModel()?->getPrimaryKey());
        self::assertSame([$user->getAttributeLabel('email') => 2, $user->getAttributeLabel('name') => 1], $references[0]->getAttributeLabelCounts());

        self::assertNull($references[1]->getModel());
        self::assertSame(['Å.東京' => 1], $references[1]->getAttributeLabelCounts());
    }

    public function testTheGridLeadsToAnOwnerTheUserMayOpen(): void
    {
        $user = $this->getUserFromFixture('admin');

        $grid = ModelReferenceGridView::make()
            ->provider(new ArrayDataProvider([
                'allModels' => [
                    $this->createReference(User::class, $user->id, ['email' => 2]),
                    $this->createReference('App\\Gone', 7, ['name' => 1]),
                ],
                'pagination' => false,
            ]));

        $html = (string)$grid;

        self::assertStringContainsString($user->getAttributeLabel('email') . ' (2x)', $html);
        self::assertStringContainsString('App\\Gone #7', $html);
        self::assertStringNotContainsString("/admin/user/update?id=$user->id", $html);

        $this->assignAdminRole($user->id);
        $this->getWebUser()->login($user);

        $html = (string)ModelReferenceGridView::make()
            ->provider(new ArrayDataProvider([
                'allModels' => [$this->createReference(User::class, $user->id, ['email' => 1])],
                'pagination' => false,
            ]));

        self::assertStringContainsString("/admin/user/update?id=$user->id", $html);
    }

    /**
     * @param array<string, int> $attributeCounts
     */
    private function createReference(string $modelClass, int $modelId, array $attributeCounts): ModelReferenceRecord
    {
        $reference = new ModelReferenceRecord();
        $reference->model_class = $modelClass;
        $reference->model_id = $modelId;
        $reference->attribute_counts = $attributeCounts;

        return $reference;
    }
}

class ModelReferenceRecord extends ActiveRecord implements ModelReferenceInterface
{
    use ModelReferenceTrait;

    #[Override]
    public static function tableName(): string
    {
        return 'model_reference_test';
    }
}

/**
 * @extends GridView<ModelReferenceRecord>
 */
class ModelReferenceGridView extends GridView
{
    use ModelReferenceGridTrait;

    #[Override]
    protected function configure(): void
    {
        $this->columns ??= $this->getModelReferenceColumns();
        parent::configure();
    }
}
