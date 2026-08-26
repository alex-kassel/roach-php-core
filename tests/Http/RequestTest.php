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

namespace RoachPHP\Tests\Http;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\Spider\ParseResult;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTestCase;

/**
 * @group http
 *
 * @internal
 */
final class RequestTest extends TestCase
{
    use DroppableTestCase;
    use InteractsWithRequestsAndResponses;

    public function test_can_access_the_request_uri(): void
    {
        $request = $this->makeRequest('::request-uri::');

        self::assertSame('::request-uri::', $request->getUri());
    }

    public function test_can_access_the_request_uri_path(): void
    {
        $request = $this->makeRequest('https://example.com/::path::');

        self::assertSame('/::path::', $request->getPath());
    }

    public function test_can_add_header(): void
    {
        $request = $this->makeRequest();

        self::assertFalse($request->hasHeader('X-Custom-Header'));

        $newRequest = $request->addHeader('X-Custom-Header', '::value::');

        self::assertFalse($request->hasHeader('X-Custom-Header'));
        self::assertTrue($newRequest->hasHeader('X-Custom-Header'));
        self::assertSame(['::value::'], $newRequest->getHeader('X-Custom-Header'));
    }

    public function test_can_manipulate_underlying_guzzle_request(): void
    {
        $request = $this->makeRequest();

        self::assertFalse($request->hasHeader('X-Custom-Header'));

        $request->withPsrRequest(static function (Request $guzzleRequest) {
            return $guzzleRequest->withHeader('X-Custom-Header', '::value::');
        });

        self::assertTrue($request->hasHeader('X-Custom-Header'));
        self::assertSame(['::value::'], $request->getHeader('X-Custom-Header'));
    }

    public function test_can_call_parse_callback(): void
    {
        $called = false;
        $request = $this->makeRequest(callback: static function (Response $response) use (&$called) {
            $called = true;

            yield ParseResult::item(['::item::']);
        });

        $request->callback(
            new Response(new GuzzleResponse, $request),
        )->next();

        self::assertTrue($called);
    }

    public function test_can_add_meta_data_to_request(): void
    {
        $request = $this->makeRequest();

        self::assertNull($request->getMeta('::meta-key::'));

        $request = $request->withMeta('::meta-key::', '::meta-value::');
        self::assertSame('::meta-value::', $request->getMeta('::meta-key::'));
    }

    public function test_returns_underlying_guzzle_request(): void
    {
        $request = $this->makeRequest('::request-uri::');

        self::assertSame('::request-uri::', (string) $request->getPsrRequest()->getUri());
    }

    public function test_adding_response_doesnt_mutate_request(): void
    {
        $requestA = $this->makeRequest('::request-uri::');

        $response = $this->makeResponse($requestA);

        $requestB = $requestA->withResponse($response);

        self::assertNotSame($requestA, $requestB);
        self::assertNull($requestA->getResponse());
        self::assertSame($response, $requestB->getResponse());
    }

    public function test_return_parsed_url(): void
    {
        $request = $this->makeRequest('https://example.com/path#anchor');

        self::assertTrue(
            $request->url->equals('https://example.com/path#anchor'),
        );
    }

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeRequest();
    }
}
