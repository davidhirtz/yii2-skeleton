<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Models\CustomAttributes\IconCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class IconCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(IconRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(IconRecord::tableName())
            ->execute();
    }

    public function testTheUrlNamesTheIconInsideThePath(): void
    {
        $this->createIcon('Mu' . "\u{308}" . 'ller ✆.svg', 1_700_000_000);

        $record = new IconRecord();
        self::assertNull($this->getDefinition($record, 'icon')->getUrl($record, 'icon'));

        $record->setAttribute('icon', 'Mu' . "\u{308}" . 'ller ✆.svg');
        self::assertSame(
            '/images/icons/Mu%CC%88ller%20%E2%9C%86.svg',
            $this->getDefinition($record, 'icon')->getUrl($record, 'icon'),
        );
    }

    public function testTheUrlCarriesTheVersionWhenAsked(): void
    {
        $this->createIcon('arrow.svg', 1_700_000_000);

        $record = new IconRecord();
        $record->setAttribute('versioned', 'arrow.svg');

        self::assertSame(
            '/images/icons/arrow.svg?1700000000',
            $this->getDefinition($record, 'versioned')->getUrl($record, 'versioned'),
        );
    }

    public function testAMissingFileAnswersWithoutTime(): void
    {
        $record = new IconRecord();
        $record->setAttribute('versioned', 'missing.svg');

        self::assertSame(
            '/images/icons/missing.svg',
            $this->getDefinition($record, 'versioned')->getUrl($record, 'versioned'),
        );
    }

    private function createIcon(string $filename, int $time): void
    {
        $dir = Yii::getAlias('@webroot/images/icons');
        FileHelper::createDirectory($dir);

        self::assertNotFalse(file_put_contents("$dir/$filename", '<svg/>'));
        self::assertTrue(touch("$dir/$filename", $time));
    }

    private function getDefinition(IconRecord $record, string $name): IconCustomAttribute
    {
        $definition = $record->getCustomAttribute($name);
        self::assertInstanceOf(IconCustomAttribute::class, $definition);

        return $definition;
    }
}

class IconRecord extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [
            IconCustomAttribute::make('icon'),
            IconCustomAttribute::make('versioned')->withVersion(),
        ];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'icon_custom_attribute_test';
    }
}
