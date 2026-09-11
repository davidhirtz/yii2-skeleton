<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\Model;

class CustomAttributeFingerprintTest extends TestCase
{
    public function testTwoEqualDefinitionsShareTheirFingerprint(): void
    {
        self::assertSame(
            TextCustomAttribute::make('subtitle')->max(20)->getFingerprint(),
            TextCustomAttribute::make('subtitle')->max(20)->getFingerprint(),
        );
    }

    public function testAnOptionChangesTheFingerprint(): void
    {
        $fingerprint = TextCustomAttribute::make('subtitle')->max(20)->getFingerprint();

        self::assertNotSame($fingerprint, TextCustomAttribute::make('subtitle')->max(21)->getFingerprint());
        self::assertNotSame($fingerprint, TextCustomAttribute::make('subtitle')->max(20)->multiline()->getFingerprint());
        self::assertNotSame($fingerprint, TextCustomAttribute::make('subtitle')->max(20)->translatable()->getFingerprint());
        self::assertNotSame($fingerprint, TextCustomAttribute::make('subtitle')->max(20)->label('Other')->getFingerprint());
        self::assertNotSame($fingerprint, TextCustomAttribute::make('other')->max(20)->getFingerprint());
    }

    /**
     * A closure is evaluated against the same model state for every type, so only the definition list differs.
     */
    public function testClosuresDoNotChangeTheFingerprint(): void
    {
        $fingerprint = TextCustomAttribute::make('subtitle')->getFingerprint();

        self::assertSame($fingerprint, TextCustomAttribute::make('subtitle')
            ->required(static fn (Model $model): bool => true)
            ->visible(static fn (Model $model): bool => false)
            ->disabled(static fn (Model $model): bool => true)
            ->getFingerprint());

        self::assertSame(
            SelectCustomAttribute::make('layout')->options(static fn (Model $model): array => [1 => 'One'])->getFingerprint(),
            SelectCustomAttribute::make('layout')->options(static fn (Model $model): array => [2 => 'Two'])->getFingerprint(),
        );
    }

    public function testStaticSelectOptionKeysChangeTheFingerprint(): void
    {
        self::assertSame(
            SelectCustomAttribute::make('layout')->options([1 => 'One'])->getFingerprint(),
            SelectCustomAttribute::make('layout')->options([1 => 'Other label'])->getFingerprint(),
        );

        self::assertNotSame(
            SelectCustomAttribute::make('layout')->options([1 => 'One'])->getFingerprint(),
            SelectCustomAttribute::make('layout')->options([2 => 'One'])->getFingerprint(),
        );
    }

    public function testGroupFingerprintCoversItsChildrenInOrder(): void
    {
        $fingerprint = GroupCustomAttribute::make('links')
            ->attributes([TextCustomAttribute::make('label'), TextCustomAttribute::make('url')])
            ->getFingerprint();

        self::assertSame($fingerprint, GroupCustomAttribute::make('links')
            ->attributes([TextCustomAttribute::make('label'), TextCustomAttribute::make('url')])
            ->getFingerprint());

        self::assertNotSame($fingerprint, GroupCustomAttribute::make('links')
            ->attributes([TextCustomAttribute::make('url'), TextCustomAttribute::make('label')])
            ->getFingerprint());

        self::assertNotSame($fingerprint, GroupCustomAttribute::make('links')
            ->attributes([TextCustomAttribute::make('label'), TextCustomAttribute::make('url')->max(20)])
            ->getFingerprint());

        self::assertNotSame($fingerprint, GroupCustomAttribute::make('links')
            ->attributes([TextCustomAttribute::make('label'), TextCustomAttribute::make('url')])
            ->maxCount(5)
            ->getFingerprint());
    }
}
