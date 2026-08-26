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

namespace RoachPHP\Tests\Downloader\Proxy;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Proxy\ProxyOptions;

/**
 * @internal
 */
final class ProxyOptionsTest extends TestCase
{
    public function test_can_be_converted_to_an_array(): void
    {
        $proxy = new ProxyOptions(
            '::http-proxy::',
            '::https-proxy::',
            ['::excluded-domain::'],
        );

        self::assertSame(
            [
                'http' => '::http-proxy::',
                'https' => '::https-proxy::',
                'no' => ['::excluded-domain::'],
            ],
            $proxy->toArray(),
        );
    }

    public function test_contains_no_options_by_default(): void
    {
        $proxy = new ProxyOptions;

        self::assertSame([], $proxy->toArray());
    }

    public function test_configure_http_proxy_url(): void
    {
        $proxy = ProxyOptions::make()->http('::http-proxy::');

        self::assertSame(
            ['http' => '::http-proxy::'],
            $proxy->toArray(),
        );
    }

    public function test_configure_https_proxy_url(): void
    {
        $proxy = ProxyOptions::make()->https('::https-proxy::');

        self::assertSame(
            ['https' => '::https-proxy::'],
            $proxy->toArray(),
        );
    }

    public function test_configure_multiple_excluded_domains(): void
    {
        $proxy = ProxyOptions::make()
            ->exclude(['::domain-1::', '::domain-2::']);

        self::assertSame(
            ['no' => ['::domain-1::', '::domain-2::']],
            $proxy->toArray(),
        );
    }

    public function test_configure_single_excluded_domain_as_string(): void
    {
        $proxy = ProxyOptions::make()
            ->exclude('::excluded-domain::');

        self::assertSame(
            ['no' => ['::excluded-domain::']],
            $proxy->toArray(),
        );
    }

    public function test_configure_same_proxy_for_http_and_https(): void
    {
        $proxy = ProxyOptions::allProtocols('::proxy-url::');

        self::assertSame(
            [
                'http' => '::proxy-url::',
                'https' => '::proxy-url::',
            ],
            $proxy->toArray(),
        );
    }

    public function test_object_is_immutable(): void
    {
        $proxy1 = ProxyOptions::make()->http('::http-proxy-1::');
        $proxy2 = $proxy1->http('::http-proxy-2::');

        self::assertSame(
            ['http' => '::http-proxy-1::'],
            $proxy1->toArray(),
        );
        self::assertSame(
            ['http' => '::http-proxy-2::'],
            $proxy2->toArray(),
        );
    }

    public function test_equality(): void
    {
        $proxy1 = new ProxyOptions(
            '::http-proxy-1::',
            '::https-proxy-1::',
            ['::excluded-domain-1::', '::excluded-domain-2::'],
        );
        $proxy2 = new ProxyOptions(
            '::http-proxy-2::',
            '::https-proxy-2::',
            ['::excluded-domain-3::'],
        );
        $proxy3 = new ProxyOptions(
            '::http-proxy-1::',
            '::https-proxy-1::',
            ['::excluded-domain-1::', '::excluded-domain-2::'],
        );

        self::assertTrue($proxy1->equals($proxy1));
        self::assertFalse($proxy1->equals($proxy2));
        self::assertTrue($proxy1->equals($proxy3));

        self::assertTrue($proxy2->equals($proxy2));
        self::assertFalse($proxy2->equals($proxy1));
        self::assertFalse($proxy2->equals($proxy3));

        self::assertTrue($proxy3->equals($proxy3));
        self::assertTrue($proxy3->equals($proxy1));
        self::assertFalse($proxy3->equals($proxy2));
    }

    public function test_fluent_interface(): void
    {
        $proxy = ProxyOptions::make()
            ->http('::http-proxy::')
            ->https('::https-proxy::')
            ->exclude('::excluded-domain::');

        self::assertSame(
            [
                'http' => '::http-proxy::',
                'https' => '::https-proxy::',
                'no' => ['::excluded-domain::'],
            ],
            $proxy->toArray(),
        );
    }

    #[DataProvider('emptyProxyProvider')]
    public function test_empty(ProxyOptions $options, bool $expected): void
    {
        self::assertEquals($expected, $options->isEmpty());
    }

    public static function emptyProxyProvider(): array
    {
        return [
            'only http' => [
                new ProxyOptions('::http-proxy-url::'),
                false,
            ],
            'only https' => [
                new ProxyOptions(httpsProxyURL: '::https-proxy-url::'),
                false,
            ],
            'only excluded' => [
                new ProxyOptions(excludedDomains: ['::domain::']),
                false,
            ],
            'not empty' => [
                new ProxyOptions,
                true,
            ],
        ];
    }
}
