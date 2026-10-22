<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Collections;

use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use RuntimeException;
use Yii;
use yii\base\Model;
use yii\log\Logger;

class TrailModelCollectionTest extends TestCase
{
    public function testAClassThatFailsToBuildWarnsOnce(): void
    {
        $this->logger->isRecording = true;
        $this->logger->messages = [];

        self::assertNull(TrailModelCollection::getModelByClassAndId(TrailModelCollectionTestBroken::class, 1));
        self::assertNull(TrailModelCollection::getModelByClassAndId(TrailModelCollectionTestBroken::class, 2));

        $levels = array_column(array_filter(
            $this->logger->messages,
            fn (array $message): bool => $message[0] === 'Broken by the project.',
        ), 1);

        self::assertSame([Logger::LEVEL_WARNING], $levels);
    }

    /**
     * The declaration is indexed by offset, so a type whose value is not its own offset was never found.
     */
    public function testATypeIsFormattedWithItsDefinitionName(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Redirect::create(), 'type', Redirect::TYPE_FOUND);
        self::assertSame('302 - Temporary redirect', $value);
    }

    /**
     * A declaration offset that does hold a definition used to reach the object and fatal on `$value['name']`.
     */
    public function testATypeWhoseValueCollidesWithAnotherDeclarationOffset(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Trail::create(), 'type', Trail::TYPE_UPDATE);
        self::assertSame('Updated', $value);
    }

    public function testATypeIsFormattedInTheReadersLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'de';

        $value = TrailModelCollection::formatAttributeValue(Trail::create(), 'type', Trail::TYPE_UPDATE);
        self::assertSame('Aktualisiert', $value);
    }

    /**
     * A `0` was skipped as empty and shown as the number (#477).
     */
    public function testAStatusOfZeroIsFormattedWithItsDefinitionName(): void
    {
        $model = new TrailModelCollectionTestModel();

        $value = TrailModelCollection::formatAttributeValue($model, 'status', 0);
        self::assertSame('Draft', $value);

        $value = TrailModelCollection::formatAttributeValue($model, 'status', '0');
        self::assertSame('Draft', $value);
    }

    public function testAnUnknownTypeFallsBackToItsValue(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Redirect::create(), 'type', 418);
        self::assertSame(418, $value);
    }

    /**
     * A range attribute whose model answers a plain `value => label` map rather than definitions.
     */
    public function testAPlainLabelMapIsStillFormatted(): void
    {
        $value = TrailModelCollection::formatAttributeValue(User::create(), 'language', 'en-US');
        self::assertSame('English', $value);
    }
}

class TrailModelCollectionTestModel extends Model
{
    public int $status = 1;

    #[Override]
    public function rules(): array
    {
        return [
            [['status'], 'in', 'range' => [0, 1]],
        ];
    }

    /**
     * @return array<int, Status>
     */
    public function getStatusDefinitions(): array
    {
        return [
            1 => Status::make(1)->name('Enabled'),
            0 => Status::make(0)->name('Draft'),
        ];
    }
}

class TrailModelCollectionTestBroken extends Model
{
    public function __construct()
    {
        throw new RuntimeException('Broken by the project.');
    }
}
