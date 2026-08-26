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

use PHPUnit\Framework\TestCase;
use RoachPHP\Shell\InvalidSpiderException;
use RoachPHP\Shell\Resolver\StaticNamespaceResolver;
use RoachPHP\Tests\Fixtures\RequestSpiderMiddleware;

/**
 * @internal
 */
final class StaticNamespaceResolverTest extends TestCase
{
    public function test_use_provided_parameter_as_is_if_it_exists_and_is_a_valid_spider(): void
    {
        $resolver = new StaticNamespaceResolver;

        $result = $resolver->resolveSpiderNamespace('RoachPHP\Tests\Fixtures\TestSpider');

        self::assertSame('RoachPHP\Tests\Fixtures\TestSpider', $result);
    }

    public function test_throws_exception_if_the_provided_spider_class_does_not_exist(): void
    {
        $resolver = new StaticNamespaceResolver;

        $this->expectException(InvalidSpiderException::class);
        $this->expectExceptionMessage('The spider class ::spider-class:: does not exist');

        $resolver->resolveSpiderNamespace('::spider-class::');
    }

    public function test_throws_exception_if_the_provided_class_is_not_a_spider(): void
    {
        $resolver = new StaticNamespaceResolver;

        $this->expectException(InvalidSpiderException::class);
        $this->expectExceptionMessage(\sprintf('The class %s is not a spider', RequestSpiderMiddleware::class));

        $resolver->resolveSpiderNamespace(RequestSpiderMiddleware::class);
    }
}
