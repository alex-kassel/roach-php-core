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

namespace RoachPHP\Tests\Http;

use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Query;
use RoachPHP\Http\QueryParameterTypeMismatchException;
use RoachPHP\Http\UnknownQueryParameterException;

/**
 * @internal
 */
final class QueryTest extends TestCase
{
    public function test_can_be_converted_to_and_from_an_array(): void
    {
        $query = Query::fromArray([
            'baz' => 'qux',
            'foo' => 'bar',
        ]);

        self::assertSame([
            'baz' => 'qux',
            'foo' => 'bar',
        ], $query->toArray());
    }

    public function test_can_be_constructed_from_a_string(): void
    {
        $query = Query::parse('foo=bar&baz[]=1&baz[]=2');
        $expectedQuery = Query::fromArray([
            'foo' => 'bar',
            'baz' => ['1', '2'],
        ]);

        self::assertTrue($query->equals($expectedQuery));
    }

    public function test_can_be_turned_into_a_string(): void
    {
        $query = Query::fromArray([
            'foo' => 'bar',
            'baz' => ['qux', 'bla'],
        ]);

        self::assertSame(
            'baz%5B0%5D=qux&baz%5B1%5D=bla&foo=bar',
            $query->toString(),
        );
    }

    public function test_check_if_key_exists(): void
    {
        $query = Query::fromArray([
            '::key-1::' => '::value-1::',
        ]);

        self::assertTrue($query->has('::key-1::'));
        self::assertFalse($query->has('::key-2::'));
    }

    public function test_get_value(): void
    {
        $query = Query::fromArray([
            '::key-1::' => '::value-1::',
            '::key-2::' => ['::value-2::', 2],
        ]);

        self::assertSame('::value-1::', $query->get('::key-1::'));
        self::assertSame(['::value-2::', 2], $query->get('::key-2::'));
    }

    public function test_throw_exception_when_trying_to_get_non_existent_key(): void
    {
        $this->expectException(UnknownQueryParameterException::class);

        $query = Query::fromArray([
            '::key-1::' => '::value-1::',
        ]);

        $query->get('::key-2::');
    }

    public function test_get_value_with_default(): void
    {
        $query = Query::fromArray(['::key-1::' => '::value-1::']);

        self::assertNull($query->tryGet('::key-2::'));
        self::assertSame(
            '::default::',
            $query->tryGet('::key-2::', '::default::'),
        );
    }

    public function test_get_integer(): void
    {
        $query = Query::fromArray(['::key::' => '42']);

        self::assertSame(42, $query->getInt('::key::'));
    }

    public function test_get_integer_throws_exception_if_value_is_not_numeric(): void
    {
        $this->expectException(QueryParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Unable to cast non-numeric parameter [::key::] to an integer');

        $query = Query::fromArray(['::key::' => '::value::']);

        $query->getInt('::key::');
    }

    public function test_get_array(): void
    {
        $query = Query::fromArray(['::key::' => ['::value-1::', '::value-2::']]);

        self::assertSame(
            ['::value-1::', '::value-2::'],
            $query->getArray('::key::'),
        );
    }

    public function test_get_array_throws_exception_if_value_is_not_an_array(): void
    {
        $this->expectException(QueryParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Parameter [::key::] is not an array');

        $query = Query::fromArray(['::key::' => '::value::']);

        $query->getArray('::key::');
    }

    public function test_get_float(): void
    {
        $query = Query::fromArray(['::key::' => '69.420']);

        self::assertSame(69.420, $query->getFloat('::key::'));
    }

    public function test_get_float_throws_exception_if_value_is_not_numeric(): void
    {
        $this->expectException(QueryParameterTypeMismatchException::class);
        $this->expectExceptionMessage('Unable to cast non-numeric parameter [::key::] to a float');

        $query = Query::fromArray(['::key::' => '::value::']);

        $query->getFloat('::key::');
    }

    public function test_equality(): void
    {
        $query1 = Query::fromArray([
            '::key-1::' => '::value-1::',
            '::key-2::' => [
                '::value-2::',
                '::value-3::',
            ],
        ]);
        $query2 = Query::fromArray([
            '::key-3::' => '::value-4::',
        ]);
        $query3 = Query::fromArray([
            '::key-1::' => '::value-1::',
            '::key-2::' => [
                '::value-2::',
                '::value-3::',
            ],
        ]);

        self::assertTrue($query1->equals($query1));
        self::assertFalse($query1->equals($query2));
        self::assertTrue($query1->equals($query3));

        self::assertFalse($query2->equals($query1));
        self::assertTrue($query2->equals($query2));
        self::assertFalse($query2->equals($query3));

        self::assertTrue($query3->equals($query1));
        self::assertFalse($query3->equals($query2));
        self::assertTrue($query3->equals($query3));
    }

    public function test_compare_equality_with_string(): void
    {
        $query1 = Query::fromArray([
            'foo' => 'bar',
            'baz' => ['qux', 'bla'],
        ]);

        self::assertTrue(
            $query1->equals('foo=bar&baz%5B0%5D=qux&baz%5B1%5D=bla'),
        );
        self::assertFalse(
            $query1->equals('foo=bar&baz%5B0%5D=qux&baz%5B1%5D=derp'),
        );
        self::assertFalse(
            $query1->equals('foo=bar'),
        );
    }

    public function test_comparing_for_equality_ignores_order_of_parameters(): void
    {
        $query1 = Query::fromArray([
            'foo' => 'bar',
            'baz' => ['qux', 'bla'],
        ]);
        $query2 = Query::fromArray([
            'baz' => ['qux', 'bla'],
            'foo' => 'bar',
        ]);

        self::assertTrue($query1->equals($query2));
    }

    public function test_is_empty(): void
    {
        $query1 = Query::fromArray(['::key::' => '::value::']);
        $query2 = Query::fromArray(['::key::' => null]);
        $query3 = Query::fromArray([]);

        self::assertFalse($query1->isEmpty());
        self::assertFalse($query2->isEmpty());
        self::assertTrue($query3->isEmpty());
    }
}
