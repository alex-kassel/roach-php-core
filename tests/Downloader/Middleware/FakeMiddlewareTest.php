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

namespace RoachPHP\Tests\Downloader\Middleware;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\FakeMiddleware;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class FakeMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    public function test_return_request_unchanged_by_default(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();

        $result = $middleware->handleRequest($request);

        self::assertSame($request, $result);
    }

    public function test_calls_request_handler_callback_if_provided(): void
    {
        $middleware = new FakeMiddleware(static fn (Request $request) => $request->drop('::reason::'));
        $request = $this->makeRequest();

        $result = $middleware->handleRequest($request);

        self::assertTrue($result->wasDropped());
    }

    public function test_assert_request_handled_passes_when_middleware_was_called_with_correct_request(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();

        $middleware->handleRequest($request);

        $middleware->assertRequestHandled($request);
    }

    public function test_assert_request_handled_fails_if_middleware_was_not_called_at_all(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();

        $this->expectException(AssertionFailedError::class);
        $middleware->assertRequestHandled($request);
    }

    public function test_assert_request_handled_fails_if_middleware_was_not_called_with_request(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();
        $otherRequest = $this->makeRequest();

        $middleware->handleRequest($otherRequest);

        $this->expectException(AssertionFailedError::class);
        $middleware->assertRequestHandled($request);
    }

    public function test_assert_request_not_handled(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();
        $otherRequest = $this->makeRequest();

        $middleware->assertRequestNotHandled($request);

        $middleware->handleRequest($otherRequest);
        $middleware->assertRequestNotHandled($request);

        $middleware->handleRequest($request);
        $this->expectException(AssertionFailedError::class);
        $middleware->assertRequestNotHandled($request);
    }

    public function test_assert_no_requests_handled(): void
    {
        $middleware = new FakeMiddleware;
        $request = $this->makeRequest();

        $middleware->assertNoRequestsHandled();

        $middleware->handleRequest($request);
        $this->expectException(AssertionFailedError::class);
        $middleware->assertNoRequestsHandled();
    }

    public function test_return_response_unchanged_by_default(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();

        $result = $middleware->handleResponse($response);

        self::assertSame($response, $result);
    }

    public function test_calls_response_handler_callback_if_provided(): void
    {
        $middleware = new FakeMiddleware(null, static fn (Response $response) => $response->drop('::reason::'));
        $response = $this->makeResponse();

        $result = $middleware->handleResponse($response);

        self::assertTrue($result->wasDropped());
    }

    public function test_assert_response_handled_passes_when_middleware_was_called_with_correct_request(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();

        $middleware->handleResponse($response);

        $middleware->assertResponseHandled($response);
    }

    public function test_assert_response_handled_fails_if_middleware_was_not_called_at_all(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();

        $this->expectException(AssertionFailedError::class);
        $middleware->assertResponseHandled($response);
    }

    public function test_assert_response_handled_fails_if_middleware_was_not_called_with_request(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();
        $otherResponse = $this->makeResponse();

        $middleware->handleResponse($otherResponse);

        $this->expectException(AssertionFailedError::class);
        $middleware->assertResponseHandled($response);
    }

    public function test_assert_response_not_handled(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();
        $otherResponse = $this->makeResponse();

        $middleware->assertResponseNotHandled($response);

        $middleware->handleResponse($otherResponse);
        $middleware->assertResponseNotHandled($response);

        $middleware->handleResponse($response);
        $this->expectException(AssertionFailedError::class);
        $middleware->assertResponseNotHandled($response);
    }

    public function test_assert_no_response_handled(): void
    {
        $middleware = new FakeMiddleware;
        $response = $this->makeResponse();

        $middleware->assertNoResponseHandled();

        $middleware->handleResponse($response);
        $this->expectException(AssertionFailedError::class);
        $middleware->assertNoResponseHandled();
    }
}
