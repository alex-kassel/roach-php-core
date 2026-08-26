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

namespace RoachPHP\Tests;

use PHPUnit\Framework\TestCase;
use RoachPHP\Core\FakeRunner;
use RoachPHP\Roach;
use RoachPHP\Tests\Fixtures\TestSpider;

/**
 * @internal
 */
final class RoachTest extends TestCase
{
    protected function tearDown(): void
    {
        Roach::restore();
    }

    public function test_faking_runner_returns_runner_fake(): void
    {
        $runner = Roach::fake();

        self::assertInstanceOf(FakeRunner::class, $runner);
    }

    public function test_use_fake_runner_if_it_exists(): void
    {
        $runner = Roach::fake();

        Roach::startSpider(TestSpider::class);

        $runner->assertRunWasStarted(TestSpider::class);
    }
}
