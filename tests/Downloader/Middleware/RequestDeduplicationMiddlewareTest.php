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

use PHPUnit\Framework\TestCase;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;

/**
 * @group downloader
 * @group middleware
 *
 * @internal
 */
final class RequestDeduplicationMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    private RequestDeduplicationMiddleware $middleware;

    private FakeLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger;
        $this->middleware = new RequestDeduplicationMiddleware($this->logger);
    }

    public function test_drops_request_if_it_was_already_seen_before(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([]);

        $request = $this->middleware->handleRequest($request);
        self::assertFalse($request->wasDropped());

        $request = $this->middleware->handleRequest($request);
        self::assertTrue($request->wasDropped());
    }

    public function test_passes_request_along_if_it_hasnt_been_seen_before(): void
    {
        $requestA = $this->makeRequest('https://example.com/a');
        $requestB = $this->makeRequest('https://example.com/b');
        $this->middleware->configure([]);

        $requestA = $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertFalse($requestA->wasDropped());
        self::assertFalse($requestB->wasDropped());
    }

    public function test_log_dropped_requests_if_logger_was_provided(): void
    {
        $request = $this->makeRequest('https://example.com');
        $this->middleware->configure([]);

        $this->middleware->handleRequest($request);
        $this->middleware->handleRequest($request);

        self::assertTrue(
            $this->logger->messageWasLogged(
                'info',
                '[RequestDeduplicationMiddleware] Dropping duplicate request',
                ['uri' => 'https://example.com'],
            ),
        );
    }

    public function test_ignores_trailing_slashes_by_default_when_comparing_urls(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com/');
        $this->middleware->configure([]);

        $this->middleware->handleRequest($requestA);

        $requestB = $this->middleware->handleRequest($requestB);
        self::assertTrue($requestB->wasDropped());
    }

    public function test_can_be_configured_to_include_trailing_slashes_when_comparing_urls(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com/');
        $this->middleware->configure(['ignore_trailing_slashes' => false]);

        $requestA = $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertFalse($requestA->wasDropped());
        self::assertFalse($requestB->wasDropped());
    }

    public function test_handles_trailing_slashes_correctly_when_url_has_fragments(): void
    {
        $requestA = $this->makeRequest('https://example.com#fragment');
        $requestB = $this->makeRequest('https://example.com/#fragment');
        $this->middleware->configure([
            'ignore_trailing_slashes' => true,
            'ignore_url_fragments' => false,
        ]);

        $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertTrue($requestB->wasDropped());
    }

    public function test_includes_url_fragments_by_default_when_comparing_urls(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com#fragment');

        $requestA = $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertFalse($requestA->wasDropped());
        self::assertFalse($requestB->wasDropped());
    }

    public function test_can_be_configured_to_ignore_url_fragments(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com#fragment');
        $this->middleware->configure(['ignore_url_fragments' => true]);

        $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertTrue($requestB->wasDropped());
    }

    public function test_includes_query_string_by_default_when_comparing_urls(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com?foo=bar');
        $this->middleware->configure([]);

        $requestA = $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertFalse($requestA->wasDropped());
        self::assertFalse($requestB->wasDropped());
    }

    public function test_can_be_configured_to_ignore_query_strings_when_comparing_urls(): void
    {
        $requestA = $this->makeRequest('https://example.com');
        $requestB = $this->makeRequest('https://example.com?foo=bar');
        $this->middleware->configure(['ignore_query_string' => true]);

        $this->middleware->handleRequest($requestA);
        $requestB = $this->middleware->handleRequest($requestB);

        self::assertTrue($requestB->wasDropped());
    }
}
