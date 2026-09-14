<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Types;

use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Statuses\Status;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\Types\TrailType;
use Hirtz\Skeleton\Models\Types\Type;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use yii\base\InvalidConfigException;

class TypeTest extends TestCase
{
    public function testTheValueIsTheConstructorArgument(): void
    {
        self::assertSame(3, Type::make(3)->value);
    }

    public function testAnIntBackedEnumIsUnwrappedToItsValue(): void
    {
        self::assertSame(3, Type::make(TypeTestEnum::Third)->value);
    }

    public function testAStringBackedEnumIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);
        Type::make(TypeTestStringEnum::Third);
    }

    /**
     * Not inflected: `Inflector::pluralize()` is English-only, and the plural is what the admin navigation shows.
     */
    public function testPluralDefaultsToTheName(): void
    {
        self::assertSame('Seite', Type::make(1)->name('Seite')->getPlural());
        self::assertSame('Seiten', Type::make(1)->name('Seite')->plural('Seiten')->getPlural());
    }

    public function testUndeclaredValuesAreEmpty(): void
    {
        $type = Type::make(1);

        self::assertSame('', $type->getName());
        self::assertSame('', $type->getPlural());
        self::assertSame('', $type->getIcon());
        self::assertNull($type->getModelClass());
        self::assertSame([], $type->getHiddenFields());
        self::assertSame([], $type->getCustomAttributes(User::create()));
        self::assertTrue($type->isAvailable(null));
    }

    public function testHiddenFieldsIsVariadic(): void
    {
        self::assertSame(['name', 'content'], Type::make(1)->hiddenFields('name', 'content')->getHiddenFields());
        self::assertSame([], Type::make(1)->hiddenFields()->getHiddenFields());
    }

    public function testCustomAttributesAcceptAClosure(): void
    {
        $user = User::create();
        $definition = TextCustomAttribute::make('subtitle');

        $type = Type::make(1)->customAttributes(fn (): array => [$definition]);
        self::assertSame([$definition], $type->getCustomAttributes($user));

        $type = Type::make(1)->customAttributes([$definition]);
        self::assertSame([$definition], $type->getCustomAttributes($user));
    }

    public function testAvailableAcceptsAClosure(): void
    {
        $type = Type::make(1)->available(fn (?User $user): bool => $user?->id === 1);

        self::assertFalse($type->isAvailable(null));
        self::assertFalse($type->isAvailable(User::create()));

        $user = User::create();
        $user->id = 1;

        self::assertTrue($type->isAvailable($user));
        self::assertFalse(Type::make(1)->available(false)->isAvailable($user));
    }

    public function testANamelessTypeIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);
        Type::make(1)->validate(Redirect::class);
    }

    public function testANamelessStatusIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);
        Status::make(1)->validate(User::class);
    }

    public function testAForeignModelClassIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);

        Type::make(1)
            ->name('Name')
            ->modelClass(User::class)
            ->validate(Redirect::class);
    }

    public function testASubclassIsAValidModelClass(): void
    {
        Type::make(1)
            ->name('Name')
            ->modelClass(Redirect::class)
            ->validate(Redirect::class);

        $this->expectNotToPerformAssertions();
    }

    public function testAnUndeclaredParentTrailTypeIsInvalid(): void
    {
        $this->expectException(InvalidConfigException::class);

        TrailType::make(1)
            ->name('Name')
            ->parentType(9999)
            ->validate(Trail::class);
    }

    public function testTrailTypeOptions(): void
    {
        $type = TrailType::make(1)->name('Name');

        self::assertNull($type->getMessage());
        self::assertNull($type->getParentType());
        self::assertFalse($type->hasDataModelEnabled());

        self::assertTrue($type->hasDataModel()->hasDataModelEnabled());
    }
}

enum TypeTestEnum: int
{
    case Third = 3;
}

enum TypeTestStringEnum: string
{
    case Third = 'third';
}
