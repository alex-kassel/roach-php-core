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

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoachPHP\Http\Response;
use RoachPHP\Support\DroppableInterface;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Tests\Support\DroppableTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
final class ResponseTest extends TestCase
{
    use DroppableTestCase;
    use InteractsWithRequestsAndResponses;

    public function test_can_access_dom_crawler_directly_from_response(): void
    {
        $response = $this->makeResponse(body: '<html lang="en"><body><a href="https://roach-php.dev">Docs</a></body></html>');

        $links = $response->filter('a')->links();

        self::assertCount(1, $links);
    }

    #[DataProvider('responseCodeProvider')]
    public function test_can_retrieve_status_code_of_original_response(int $statusCode): void
    {
        $response = new Response(new \GuzzleHttp\Psr7\Response($statusCode), $this->makeRequest());

        self::assertSame($statusCode, $response->getStatus());
    }

    public static function responseCodeProvider(): iterable
    {
        yield from [
            [200],
            [300],
            [301],
            [302],
            [400],
            [404],
            [500],
        ];
    }

    #[DataProvider('responseBodyProvider')]
    public function test_can_retrieve_html_body_of_original_response(callable $getBody): void
    {
        $body = '<html lang="en"><body><p>Hello, world!</p></body>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $getBody($body)),
            $this->makeRequest(),
        );

        self::assertSame($body, $response->getBody());
    }

    public static function responseBodyProvider(): iterable
    {
        yield from [
            'string' => [static fn (string $body) => $body],

            'stream' => [static function (string $body) {
                $stream = \fopen('php://memory', 'r+b');
                if (! \is_resource($stream)) {
                    throw new \RuntimeException('Failed to open memory stream');
                }
                \fwrite($stream, $body);
                \rewind($stream);

                return $stream;
            }],

            'StreamInterface' => [static function (string $body) {
                $stream = \fopen('php://memory', 'r+b');
                if (! \is_resource($stream)) {
                    throw new \RuntimeException('Failed to open memory stream');
                }
                \fwrite($stream, $body);
                \rewind($stream);

                return new Stream($stream);
            }],
        ];
    }

    public function test_can_update_response_body(): void
    {
        $originalBody = '<html lang="en"><body><p>Old</p></body></html>';
        $newBody = '<html lang="en"><body><p>New</p></body></html>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $originalBody),
            $this->makeRequest(),
        );

        $response = $response->withBody($newBody);

        self::assertSame($newBody, $response->getBody());
    }

    public function test_updating_response_body_updates_crawler(): void
    {
        $originalBody = '<html lang="en"><body><p>Old</p></body></html>';
        $newBody = '<html lang="en"><body><p>New</p></body></html>';
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(body: $originalBody),
            $this->makeRequest(),
        );

        $response = $response->withBody($newBody);

        self::assertSame('New', $response->filter('p')->text(''));
    }

    public function test_dom_crawler_is_lazy_loaded_on_demand(): void
    {
        $response = $this->makeResponse(body: '<html lang="en"><body></body></html>');

        $crawlerProperty = new \ReflectionProperty(Response::class, 'crawler');

        self::assertNull($crawlerProperty->getValue($response));

        self::assertSame(0, $response->filter('p')->count());
        self::assertInstanceOf(Crawler::class, $crawlerProperty->getValue($response));
    }

    protected function createDroppable(): DroppableInterface
    {
        return $this->makeResponse(
            $this->makeRequest(),
        );
    }
}
