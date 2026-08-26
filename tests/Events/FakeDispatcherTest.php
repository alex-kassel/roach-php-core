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

namespace RoachPHP\Tests\Events;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;

/**
 * @internal
 */
final class FakeDispatcherTest extends TestCase
{
    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher;
    }

    public function test_assert_dispatched_passes_if_event_was_dispatched(): void
    {
        $event = new FakeEvent;
        $this->dispatcher->dispatch($event, 'event.name');

        $this->dispatcher->assertDispatched('event.name');
    }

    public function test_assert_dispatched_fails_if_no_event_was_dispatched(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertDispatched('event.name');
    }

    public function test_assert_dispatched_fails_if_callback_returns_false(): void
    {
        $this->dispatcher->dispatch(new FakeEvent, 'event.name');

        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertDispatched('event.name', static fn (FakeEvent $event) => false);
    }

    public function test_assert_dispatched_passes_if_callback_returns_true(): void
    {
        $this->dispatcher->dispatch(new FakeEvent, 'event.name');

        $this->dispatcher->assertDispatched('event.name', static fn (FakeEvent $event) => true);
    }

    public function test_assert_not_dispatched(): void
    {
        $event = new FakeEvent;

        $this->dispatcher->assertNotDispatched('event.name');

        $this->dispatcher->dispatch($event, 'event.name');
        $this->expectException(AssertionFailedError::class);
        $this->dispatcher->assertNotDispatched('event.name');
    }

    public function test_run_event_listeners(): void
    {
        $called = false;
        $this->dispatcher->listen('event.name', static function () use (&$called): void {
            $called = true;
        });

        $this->dispatcher->dispatch(new FakeEvent, 'event.name');

        self::assertTrue($called);
    }
}

final class FakeEvent
{
    public function __construct(public array $data = []) {}
}
