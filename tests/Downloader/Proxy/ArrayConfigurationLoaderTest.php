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

use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Proxy\ArrayConfigurationLoader;
use RoachPHP\Downloader\Proxy\Proxy;
use RoachPHP\Downloader\Proxy\ProxyOptions;

final class ArrayConfigurationLoaderTest extends TestCase
{
    public function test_creates_one_proxy_configuration_per_url(): void
    {
        $loader = new ArrayConfigurationLoader([
            '::host-1::' => [
                'https' => '::https-proxy-1::',
                'http' => '::http-proxy-1::',
                'no' => ['::no-1::'],
            ],
            '::host-2::' => [
                'https' => '::https-proxy-2::',
                'http' => '::http-proxy-2::',
                'no' => [],
            ],
            '::host-3::' => [
                'no' => ['::no-3::'],
            ],
        ]);

        $proxy = $loader->loadProxyConfiguration();
        self::assertEquals(
            new Proxy([
                '::host-1::' => new ProxyOptions(
                    '::http-proxy-1::',
                    '::https-proxy-1::',
                    ['::no-1::'],
                ),
                '::host-2::' => new ProxyOptions(
                    '::http-proxy-2::',
                    '::https-proxy-2::',
                    [],
                ),
                '::host-3::' => new ProxyOptions(
                    null,
                    null,
                    ['::no-3::'],
                ),
            ]),
            $proxy,
        );
    }

    public function test_creates_a_wildcard_proxy_if_only_aurl_is_provided(): void
    {
        $loader = new ArrayConfigurationLoader('::proxy-url::');

        $proxy = $loader->loadProxyConfiguration();

        self::assertEquals(
            new Proxy([
                '*' => ProxyOptions::allProtocols('::proxy-url::'),
            ]),
            $proxy,
        );
    }

    public function test_configures_the_same_url_for_all_protocols_if_only_aurl_is_provided(): void
    {
        $loader = new ArrayConfigurationLoader([
            '::host::' => '::proxy-url::',
        ]);

        $proxy = $loader->loadProxyConfiguration();

        self::assertEquals(
            new Proxy([
                '::host::' => ProxyOptions::allProtocols('::proxy-url::'),
            ]),
            $proxy,
        );
    }
}
