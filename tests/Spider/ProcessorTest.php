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

namespace RoachPHP\Tests\Spider;

use PHPUnit\Framework\TestCase;
use RoachPHP\Events\FakeDispatcher;
use RoachPHP\Events\ItemDropped;
use RoachPHP\Events\RequestDropped;
use RoachPHP\Events\ResponseDropped;
use RoachPHP\Http\Response;
use RoachPHP\ItemPipeline\Item;
use RoachPHP\Spider\Middleware\FakeHandler;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Spider\Processor;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class ProcessorTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private Processor $processor;

    private FakeDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new FakeDispatcher;
        $this->processor = new Processor($this->dispatcher);
    }

    public function test_calls_callback_on_request(): void
    {
        $parseCallback = static fn (Response $response) => yield from [];
        $expectedRequest = ParseResult::request('GET', '::new-url::', $parseCallback);
        $request = $this->makeRequest(callback: static fn () => yield $expectedRequest);
        $response = $this->makeResponse($request);

        $result = \iterator_to_array($this->processor->handle($response));

        self::assertEquals([$expectedRequest], $result);
    }

    public function test_calls_handlers_for_incoming_responses(): void
    {
        $handler = $this->makeHandler();
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item([]));
        $response = $this->makeResponse($request);

        $this->processor
            ->withMiddleware($handler)
            ->handle($response)
            ->next();

        $handler->assertResponseHandled($response);
    }

    public function test_does_not_pass_on_response_if_dropped_by_handler(): void
    {
        $dropHandler = $this->makeHandler(handleResponse: static fn ($response) => $response->drop('::reason::'));
        $otherHandler = $this->makeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $stack = $this->processor->withMiddleware($dropHandler, $otherHandler);

        $result = \iterator_to_array($stack->handle($response));

        self::assertEmpty($result);
        $otherHandler->assertNoResponseHandled();
    }

    public function test_call_response_handlers_in_order(): void
    {
        $handlerA = $this->makeHandler(static function (Response $response) {
            return $response->withMeta('foo', $response->getMeta('foo').'A');
        });
        $handlerB = $this->makeHandler(static function (Response $response) {
            return $response->withMeta('foo', $response->getMeta('foo').'B');
        });
        $request = $this->makeRequest(callback: static function (Response $response) {
            self::assertEquals('AB', $response->getMeta('foo'));

            yield ParseResult::item([]);
        });

        $this->processor
            ->withMiddleware($handlerA, $handlerB)
            ->handle($this->makeResponse($request))
            ->next();
    }

    public function test_passes_each_new_request_to_handlers_in_order(): void
    {
        $handlerA = $this->makeHandler(
            handleRequestCallback: static fn ($r) => $r->withMeta('::key::', $r->getMeta('::key::', '').'A'),
        );
        $handlerB = $this->makeHandler(
            handleRequestCallback: static fn ($r) => $r->withMeta('::key::', $r->getMeta('::key::', '').'B'),
        );
        $results = [
            ParseResult::request('GET', '::url::', static fn (Response $response) => yield from []),
            ParseResult::request('GET', '::url::', static fn (Response $response) => yield from []),
        ];
        $request = $this->makeRequest(callback: static fn () => yield from $results);
        $stack = $this->processor->withMiddleware($handlerA, $handlerB);

        $actual = \iterator_to_array($stack->handle($this->makeResponse($request)));

        self::assertSame('AB', $actual[0]->value()->getMeta('::key::'));
        self::assertSame('AB', $actual[1]->value()->getMeta('::key::'));
    }

    public function test_does_not_pass_on_request_if_dropped_by_handler(): void
    {
        $dropHandler = $this->makeHandler(handleRequestCallback: static function ($request, $response) {
            return $request->drop('::reason::');
        });
        $handlerB = $this->makeHandler();
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );
        $stack = $this->processor->withMiddleware($dropHandler, $handlerB);

        $result = \iterator_to_array($stack->handle($this->makeResponse($request)));

        $handlerB->assertNoRequestHandled();
        self::assertEmpty($result);
    }

    public function test_calls_item_handlers_in_order_for_outgoing_items(): void
    {
        $handlerA = $this->makeHandler(
            handleItemCallback: static fn ($item) => $item->set('::key::', $item->get('::key::', '').'A'),
        );
        $handlerB = $this->makeHandler(
            handleItemCallback: static fn ($item) => $item->set('::key::', $item->get('::key::', '').'B'),
        );
        $request = $this->makeRequest(callback: static function (Response $response) {
            yield ParseResult::item([]);
        });

        $result = $this->processor
            ->withMiddleware($handlerA, $handlerB)
            ->handle($this->makeResponse($request))
            ->current();

        self::assertSame('AB', $result->value()->get('::key::'));
    }

    public function test_does_not_pass_on_item_if_dropped_by_handler(): void
    {
        $dropHandler = $this->makeHandler(handleItemCallback: static function ($item, $response) {
            return $item->drop('::reason::');
        });
        $handlerB = $this->makeHandler();
        $item = new Item([]);
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::fromValue($item));
        $stack = $this->processor->withMiddleware($dropHandler, $handlerB);

        $result = \iterator_to_array($stack->handle($this->makeResponse($request)));

        $handlerB->assertNoItemHandled();
        self::assertEmpty($result);
    }

    public function test_dispatches_event_if_response_was_dropped(): void
    {
        $dropHandler = $this->makeHandler(handleResponse: static fn ($response) => $response->drop('::reason::'));
        $otherHandler = $this->makeHandler();
        $response = $this->makeResponse($this->makeRequest());
        $stack = $this->processor->withMiddleware($dropHandler, $otherHandler);

        \iterator_to_array($stack->handle($response));

        $this->dispatcher->assertDispatched(ResponseDropped::NAME);
    }

    public function test_does_not_dispatch_event_if_response_was_not_dropped(): void
    {
        $this->processor->handle($this->makeResponse())->next();

        $this->dispatcher->assertNotDispatched(ResponseDropped::NAME);
    }

    public function test_dispatch_event_if_request_was_dropped(): void
    {
        $dropHandler = $this->makeHandler(handleRequestCallback: static function ($request, $response) {
            return $request->drop('::reason::');
        });
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );

        $this->processor
            ->withMiddleware($dropHandler)
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertDispatched(
            RequestDropped::NAME,
            static fn (RequestDropped $event) => $event->request->getDropReason() === '::reason::',
        );
    }

    public function test_dont_dispatch_event_if_request_was_not_dropped(): void
    {
        $request = $this->makeRequest(
            callback: fn () => yield ParseResult::fromValue($this->makeRequest()),
        );

        $this->processor
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertNotDispatched(RequestDropped::NAME);
    }

    public function test_dispatch_event_if_item_was_dropped(): void
    {
        $dropHandler = $this->makeHandler(handleItemCallback: static function ($item) {
            return $item->drop('::reason::');
        });
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item(['foo' => 'bar']));

        $this->processor
            ->withMiddleware($dropHandler)
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertDispatched(
            ItemDropped::NAME,
            static fn (ItemDropped $event) => $event->item->all() === ['foo' => 'bar'],
        );
    }

    public function test_dont_dispatch_event_if_item_was_not_dropped(): void
    {
        $request = $this->makeRequest(callback: static fn () => yield ParseResult::item(['foo' => 'bar']));

        $this->processor
            ->handle($this->makeResponse($request))
            ->next();

        $this->dispatcher->assertNotDispatched(ItemDropped::NAME);
    }

    private function makeHandler(
        ?\Closure $handleResponse = null,
        ?\Closure $handleItemCallback = null,
        ?\Closure $handleRequestCallback = null,
    ): FakeHandler {
        return new FakeHandler($handleResponse, $handleItemCallback, $handleRequestCallback);
    }
}
