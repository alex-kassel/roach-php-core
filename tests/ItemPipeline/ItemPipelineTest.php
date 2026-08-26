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

use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\ItemScraped;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\ItemPipeline;
use RoachPHP\ItemPipeline\Processors\ConditionalItemProcessor;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

/**
 * @group items
 *
 * @internal
 */
final class ItemPipelineTest extends TestCase
{
    private ItemPipeline $pipeline;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher;
        $this->pipeline = new ItemPipeline($this->dispatcher);
    }

    public function test_calls_processors_in_order(): void
    {
        $processorA = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value').'A'),
        );
        $processorB = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value').'B'),
        );

        $result = $this->pipeline
            ->setProcessors($processorA, $processorB)
            ->sendItem(new Item(['value' => 'C']));

        self::assertSame('CAB', $result->get('value'));
    }

    public function test_dont_call_next_processors_if_item_was_dropped(): void
    {
        $processorA = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value').'A'),
        );
        $processorB = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $processorC = $this->makeProcessor(
            static fn ($item) => $item->set('value', $item->get('value').'C'),
        );

        $result = $this->pipeline
            ->setProcessors($processorA, $processorB, $processorC)
            ->sendItem(new Item(['value' => '']));

        self::assertSame('A', $result->get('value'));
    }

    public function test_dispatches_event_if_item_was_dropped(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $item = new Item(['foo' => 'bar']);

        $this->pipeline
            ->setProcessors($processor)
            ->sendItem($item);

        $this->dispatcher->assertDispatched(
            ItemDropped::NAME,
            static fn (ItemDropped $event) => $event->item->all() === $item->all(),
        );
    }

    public function test_does_not_dispatch_event_if_item_was_not_dropped(): void
    {
        $this->pipeline->sendItem(new Item([]));

        $this->dispatcher->assertNotDispatched(ItemDropped::NAME);
    }

    public function test_dispatches_event_if_item_was_scraped(): void
    {
        $this->pipeline->sendItem(new Item(['foo' => 'bar']));

        $this->dispatcher->assertDispatched(
            ItemScraped::NAME,
            static fn (ItemScraped $event) => $event->item->all() === ['foo' => 'bar'],
        );
    }

    public function test_does_not_dispatch_event_if_item_was_not_scraped(): void
    {
        $processor = $this->makeProcessor(static fn ($item) => $item->drop('::reason::'));
        $this->pipeline
            ->setProcessors($processor)
            ->sendItem(new Item([]));

        $this->dispatcher->assertNotDispatched(ItemScraped::NAME);
    }

    public function test_runs_conditional_item_processor_if_it_handles_item(): void
    {
        $processor = $this->makeConditionalProcessor(true, static fn (ItemInterface $item) => $item->drop('::reason::'));

        $result = $this->pipeline
            ->setProcessors($processor)
            ->sendItem(new Item([]));

        self::assertTrue($result->wasDropped());
    }

    public function test_does_not_run_conditional_item_processor_if_it_does_not_handle_item(): void
    {
        $processor = $this->makeConditionalProcessor(false, static fn (ItemInterface $item) => $item->drop('::reason::'));

        $result = $this->pipeline
            ->setProcessors($processor)
            ->sendItem(new Item([]));

        self::assertFalse($result->wasDropped());
    }

    private function makeProcessor(\Closure $processItem): ItemProcessorInterface
    {
        return new class($processItem) implements ItemProcessorInterface
        {
            use Configurable;

            public function __construct(private \Closure $processItem) {}

            public function processItem(ItemInterface $item): ItemInterface
            {
                return ($this->processItem)($item);
            }
        };
    }

    private function makeConditionalProcessor(
        bool $handlesItem,
        \Closure $processItem,
    ): ConditionalItemProcessor {
        return new class($handlesItem, $processItem) implements ConditionalItemProcessor
        {
            use Configurable;

            public function __construct(
                private bool $handlesItem,
                private \Closure $processItem,
            ) {}

            public function shouldHandle(ItemInterface $item): bool
            {
                return $this->handlesItem;
            }

            public function processItem(ItemInterface $item): ItemInterface
            {
                return ($this->processItem)($item);
            }
        };
    }
}
