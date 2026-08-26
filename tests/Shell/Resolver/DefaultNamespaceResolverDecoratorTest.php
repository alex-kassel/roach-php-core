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

namespace RoachPHP\Tests\Shell\Resolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Shell\Resolver\DefaultNamespaceResolverDecorator;
use RoachPHP\Shell\Resolver\FakeNamespaceResolver;
use RoachPHP\Tests\Fixtures\TestSpider;

/**
 * @internal
 */
final class DefaultNamespaceResolverDecoratorTest extends TestCase
{
    public function test_pass_input_through_unchanged_if_it_already_points_to_existing_class(): void
    {
        $result = $this->getResolver('::different-default-namespace::')->resolveSpiderNamespace(TestSpider::class);

        self::assertSame(TestSpider::class, $result);
    }

    #[DataProvider('prependNamespaceProvider')]
    public function test_prepends_default_namespace_if_passed_class_does_not_exist(string $spiderName): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace($spiderName);

        self::assertSame('RoachPHP\Tests\Fixtures\\'.$spiderName, $result);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function prependNamespaceProvider(): iterable
    {
        yield from [
            'only class name' => [
                'TestSpider',
            ],

            'relative namespace' => [
                'Derp\TestSpider',
            ],
        ];
    }

    #[DataProvider('defaultNamespaceProvider')]
    public function test_normalizes_default_namespace(string $nonNormalizedNamespace): void
    {
        $result = $this->getResolver($nonNormalizedNamespace)->resolveSpiderNamespace('TestSpider');

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function defaultNamespaceProvider(): iterable
    {
        yield from [
            'leading backslashes' => [
                '\RoachPHP\Tests\Fixtures',
            ],

            'trailing backslashes' => [
                'RoachPHP\Tests\Fixtures\\',
            ],

            'trailing spaces' => [
                'RoachPHP\Tests\Fixtures ',
            ],

            'leading spaces' => [
                ' RoachPHP\Tests\Fixtures',
            ],
        ];
    }

    #[DataProvider('spiderNameProvider')]
    public function test_normalizes_provided_spider_name(string $nonNormalizedSpiderName): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace($nonNormalizedSpiderName);

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function spiderNameProvider(): iterable
    {
        yield from [
            'leading spaces' => [
                ' TestSpider',
            ],

            'trailing spaces' => [
                'TestSpider ',
            ],
        ];
    }

    public function test_treats_leading_backslashes_as_absolute_path_and_returns_it_as_is(): void
    {
        $result = $this->getResolver()->resolveSpiderNamespace('\Test\Spider');

        self::assertSame('\Test\Spider', $result);
    }

    public function test_does_not_prepend_default_namespace_if_input_already_starts_with_it(): void
    {
        $result = $this->getResolver('::default-namespace::')->resolveSpiderNamespace('::default-namespace::\Spider');

        self::assertSame('::default-namespace::\Spider', $result);
    }

    private function getResolver(string $defaultNamespace = 'RoachPHP\Tests\Fixtures'): DefaultNamespaceResolverDecorator
    {
        return new DefaultNamespaceResolverDecorator(
            new FakeNamespaceResolver,
            $defaultNamespace,
        );
    }
}
