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

namespace RoachPHP\Tests\Spider\Middleware;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\Processors\FakeProcessor;

/**
 * @internal
 */
final class FakeProcessorTest extends TestCase
{
    public function test_pass_through_item_unchanged(): void
    {
        $item = new Item(['foo' => 'bar']);
        $processor = new FakeProcessor;

        $item = $processor->processItem($item);

        self::assertSame(['foo' => 'bar'], $item->all());
    }

    public function test_assert_called_with_passes_when_processor_was_called_with_correct_item(): void
    {
        $item = new Item(['foo' => 'bar']);
        $processor = new FakeProcessor;

        $item = $processor->processItem($item);

        $processor->assertCalledWith($item);
    }

    public function test_assert_called_with_fails_when_processor_was_not_called(): void
    {
        $item = new Item(['foo' => 'bar']);
        $processor = new FakeProcessor;

        $this->expectException(AssertionFailedError::class);
        $processor->assertCalledWith($item);
    }

    public function test_assert_called_with_fails_when_processor_was_not_called_with_correct_item(): void
    {
        $item = new Item(['::key-1::' => '::value-1::']);
        $otherItem = new Item(['::key-1::' => '::value-2::']);
        $processor = new FakeProcessor;

        $processor->processItem($item);

        $this->expectException(AssertionFailedError::class);
        $processor->assertCalledWith($otherItem);
    }

    public function test_assert_called_with_passes_if_was_called_at_least_once_with_correct_item(): void
    {
        $item1 = new Item(['::key-1::' => '::value-1::']);
        $item2 = new Item(['::key-2::' => '::value-2::']);
        $item3 = new Item(['::key-3::' => '::value-3::']);
        $processor = new FakeProcessor;

        $processor->processItem($item1);
        $processor->processItem($item2);
        $processor->processItem($item3);

        $processor->assertCalledWith($item1);
        $processor->assertCalledWith($item2);
        $processor->assertCalledWith($item3);
    }

    public function test_assert_not_called_with_fails_if_was_called_with_payload(): void
    {
        $item = new Item(['::key::' => '::value::']);
        $processor = new FakeProcessor;

        $processor->processItem($item);

        $this->expectException(AssertionFailedError::class);
        $processor->assertNotCalledWith($item);
    }

    public function test_assert_not_called_with_passes_if_processor_was_not_called_at_all(): void
    {
        $item = new Item(['::key::' => '::value::']);
        $processor = new FakeProcessor;

        $processor->assertNotCalledWith($item);
    }

    public function test_assert_not_called_with_passes_if_processor_was_not_called_with_item(): void
    {
        $item = new Item(['::key-1::' => '::value-2::']);
        $otherItem = new Item(['::key-2::' => '::value-2::']);
        $processor = new FakeProcessor;

        $processor->processItem($item);

        $processor->assertNotCalledWith($otherItem);
    }

    public function test_assert_not_called(): void
    {
        $item = new Item(['::key::' => '::value::']);
        $processor = new FakeProcessor;

        $processor->assertNotCalled();
        $processor->processItem($item);

        self::expectException(AssertionFailedError::class);
        $processor->assertNotCalled();
    }
}
