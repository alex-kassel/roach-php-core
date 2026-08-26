<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\Tests\ItemPipeline;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Tests\Fixtures\TestItem;

/**
 * @internal
 */
final class AbstractItemTest extends TestCase
{
    public function test_can_get_public_property(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::value-1::', $item->get('foo'));
        self::assertSame('::value-2::', $item->get('bar'));
    }

    public function test_return_default_value_if_no_public_property_exists_for_name(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::default::', $item->get('baz', '::default::'));
        self::assertSame('::default::', $item->get('qux', '::default::'));
        self::assertSame('::default::', $item->get('lorem-ipsum', '::default::'));
    }

    public function test_return_default_value_if_property_is_null(): void
    {
        $item = new TestItem(foo: '::value::', bar: null);

        self::assertSame('::default::', $item->get('bar', '::default::'));
    }

    public function test_can_get_all_public_properties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertEquals([
            'foo' => '::value-1::',
            'bar' => '::value-2::',
        ], $item->all());
    }

    public function test_can_set_public_property(): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $item->set('foo', '::new-value-1::');
        $item->set('bar', '::new-value-2::');

        self::assertSame('::new-value-1::', $item->foo);
        self::assertSame('::new-value-2::', $item->bar);
    }

    #[DataProvider('inaccessiblePropertiesProvider')]
    public function test_throws_exception_when_trying_to_set_non_public_or_non_existent_property(string $property): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No public property {$property} exists on class RoachPHP\\Tests\\Fixtures\\TestItem");
        $item->set($property, '::new-value::');
    }

    #[DataProvider('hasPropertyProvider')]
    public function test_has_property(string $property, bool $expected): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame($expected, $item->has($property));
    }

    #[DataProvider('hasPropertyProvider')]
    public function test_offset_exists(string $property, bool $expected): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame($expected, isset($item[$property]));
    }

    public static function hasPropertyProvider(): iterable
    {
        yield from [
            'public property 1' => ['foo', true],
            'public property 2' => ['bar', true],
            'protected property' => ['baz', false],
            'private property' => ['qux', false],
            'non-existent property' => ['does-not-exist', false],
        ];
    }

    public function test_offset_get_can_retrieve_public_properties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertSame('::value-1::', $item['foo']);
        self::assertSame('::value-2::', $item['bar']);
    }

    public function test_offset_get_returns_null_for_non_accessible_property(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertNull($item['baz']);
        self::assertNull($item['qux']);
    }

    public function test_offset_get_returns_null_for_non_existent_property(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        self::assertNull($item['does-not-exist']);
    }

    public function test_offset_get_throws_exception_if_offset_is_not_a_string(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset needs to be a string');

        $item[0];
    }

    public function test_offset_set_can_set_public_properties(): void
    {
        $item = new TestItem(foo: '::old-value-1::', bar: '::old-value-2::');

        $item['foo'] = '::new-value-1::';
        $item['bar'] = '::new-value-2::';

        self::assertSame('::new-value-1::', $item->foo);
        self::assertSame('::new-value-2::', $item->bar);
    }

    #[DataProvider('inaccessiblePropertiesProvider')]
    public function test_offset_set_throws_exception_when_setting_inaccessible_or_non_existent_property(string $property): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No public property {$property} exists on class RoachPHP\\Tests\\Fixtures\\TestItem");

        $item[$property] = '::new-value::';
    }

    public static function inaccessiblePropertiesProvider(): iterable
    {
        yield from [
            'protected property' => ['baz'],
            'private property' => ['qux'],
            'non-existent property' => ['does-not-exist'],
        ];
    }

    public function test_offset_set_throws_exception_if_offset_is_not_a_string(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset needs to be a string');

        $item[0] = '::value::';
    }

    public function test_does_not_support_unsetting_properties(): void
    {
        $item = new TestItem(foo: '::value-1::', bar: '::value-2::');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsetting properties is not supported for custom item classes');

        unset($item['foo']);
    }
}
