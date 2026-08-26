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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Spider\Middleware\MaximumCrawlDepthMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;

/**
 * @internal
 */
final class MaximumCrawlDepthMiddlewareTest extends TestCase
{
    use InteractsWithRequestsAndResponses;

    #[DataProvider('initialDepthProvider')]
    public function test_increments_crawl_depth_for_outgoing_requests_based_on_response_crawl_depth(int $initialDepth): void
    {
        $previousRequest = $this->makeRequest()->withMeta('depth', $initialDepth);
        $response = $this->makeResponse($previousRequest);

        $processedRequest = $this
            ->createMiddleware()
            ->handleRequest($this->makeRequest(), $response);

        self::assertSame($initialDepth + 1, $processedRequest->getMeta('depth'));
    }

    /**
     * @return iterable<list<int>>
     */
    public static function initialDepthProvider(): iterable
    {
        yield from [
            [0],
            [1],
            [2],
            [3],
            [4],
            [5],
        ];
    }

    public function test_handle_missing_depth_on_response(): void
    {
        $processedRequest = $this
            ->createMiddleware()
            ->handleRequest($this->makeRequest(), $this->makeResponse());

        self::assertSame(2, $processedRequest->getMeta('depth'));
    }

    #[DataProvider('maxCrawlDepthProvider')]
    public function test_drop_requests_above_the_maximum_crawl_depth(int $maxCrawlDepth): void
    {
        $previousRequest = $this
            ->makeRequest()
            // Previous request was at the maximum crawl depth already.
            ->withMeta('depth', $maxCrawlDepth);
        $response = $this->makeResponse($previousRequest);

        $processedRequest = $this
            ->createMiddleware($maxCrawlDepth)
            ->handleRequest($this->makeRequest(), $response);

        self::assertTrue($processedRequest->wasDropped());
    }

    #[DataProvider('maxCrawlDepthProvider')]
    public function test_allow_requests_below_the_maximum_crawl_depth(int $maxCrawlDepth): void
    {
        $previousRequest = $this
            ->makeRequest()
            // Previous request was still below the max crawl depth.
            ->withMeta('depth', $maxCrawlDepth - 1);

        $response = $this->makeResponse($previousRequest);

        $processedRequest = $this
            ->createMiddleware($maxCrawlDepth)
            ->handleRequest($this->makeRequest(), $response);

        self::assertFalse($processedRequest->wasDropped());
    }

    /**
     * @return iterable<list<int>>
     */
    public static function maxCrawlDepthProvider(): iterable
    {
        yield from [
            [2],
            [3],
            [4],
            [5],
        ];
    }

    private function createMiddleware(?int $maxCrawlDepth = null): MaximumCrawlDepthMiddleware
    {
        $middleware = new MaximumCrawlDepthMiddleware;

        if ($maxCrawlDepth !== null) {
            $middleware->configure(['maxCrawlDepth' => $maxCrawlDepth]);
        }

        return $middleware;
    }
}
