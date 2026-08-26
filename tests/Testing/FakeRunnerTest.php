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

namespace RoachPHP\Tests\Testing;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Core\FakeRunner;
use RoachPHP\Spider\Configuration\Overrides;
use RoachPHP\Tests\Fixtures\TestSpider;
use RoachPHP\Tests\Fixtures\TestSpider2;

/**
 * @internal
 */
final class FakeRunnerTest extends TestCase
{
    private FakeRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new FakeRunner;
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_started_passes_if_any_run_for_the_given_spider_class_was_started(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    public function test_assert_run_was_started_fails_if_no_run_was_started_at_all(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_started_fails_if_no_run_was_started_for_the_given_spider(string $method): void
    {
        $this->runner->{$method}(TestSpider2::class);

        $this->expectException(AssertionFailedError::class);
        $this->runner->assertRunWasStarted(TestSpider::class);
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_started_passes_if_the_provided_closure_returns_true(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->runner->assertRunWasStarted(TestSpider::class, static fn () => true);
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_started_passes_if_callback_returns_true_for_any_of_the_found_runs(string $method): void
    {
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'bar']);
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'baz']);
        $this->runner->{$method}(TestSpider::class, context: ['foo' => 'qux']);

        $this->runner->assertRunWasStarted(
            TestSpider::class,
            static fn (?Overrides $_, array $context): bool => $context['foo'] === 'qux',
        );
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_started_fails_if_the_provided_closure_returns_false(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->expectException(AssertionFailedError::class);
        $this->runner->assertRunWasStarted(TestSpider::class, static fn () => false);
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_not_started_passes_if_no_run_for_the_given_spider_class_was_started(string $method): void
    {
        $this->runner->{$method}(TestSpider2::class);

        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    public function test_assert_run_was_not_started_passes_if_no_run_was_started_at_all(): void
    {
        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    #[DataProvider('runnerMethodProvider')]
    public function test_assert_run_was_not_started_fails_if_run_for_spider_was_started(string $method): void
    {
        $this->runner->{$method}(TestSpider::class);

        $this->expectException(AssertionFailedError::class);

        $this->runner->assertRunWasNotStarted(TestSpider::class);
    }

    /**
     * @return iterable<array-key, array{0: string}>
     */
    public static function runnerMethodProvider(): iterable
    {
        yield from [
            'startSpider' => ['startSpider'],
            'collectSpider' => ['collectSpider'],
        ];
    }
}
