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

use RoachPHP\Downloader\Middleware\ExecuteJavascriptMiddleware;
use RoachPHP\Testing\Concerns\InteractsWithRequestsAndResponses;
use RoachPHP\Testing\FakeLogger;
use RoachPHP\Tests\IntegrationTestCase;
use Spatie\Browsershot\Browsershot;

/**
 * @internal
 */
final class ExecuteJavascriptMiddlewareTest extends IntegrationTestCase
{
    use InteractsWithRequestsAndResponses;

    public function test_update_response_body_with_html_after_executing_javascript(): void
    {
        $response = $this->makeResponse(
            $this->makeRequest('http://localhost:8000/javascript'),
        );
        $middleware = new ExecuteJavascriptMiddleware(new FakeLogger);

        $processedResponse = $middleware->handleResponse($response);

        self::assertSame('Headline', $processedResponse->filter('#content h1')->text(''));
        self::assertSame('I was loaded via Javascript!', $processedResponse->filter('#content p')->text(''));
    }

    public function test_drop_response_if_exception_occurs_while_executing_javascript(): void
    {
        $throwingBrowsershot = new class extends Browsershot
        {
            public function bodyHtml(): string
            {
                throw new \Exception('::exception-message::');
            }
        };
        $middleware = new ExecuteJavascriptMiddleware(
            new FakeLogger,
            static fn (string $uri): Browsershot => $throwingBrowsershot->setUrl($uri),
        );

        $processedResponse = $middleware->handleResponse($this->makeResponse());

        self::assertTrue($processedResponse->wasDropped());
    }

    public function test_log_errors(): void
    {
        $throwingBrowsershot = new class extends Browsershot
        {
            public function bodyHtml(): string
            {
                throw new \Exception('::exception-message::');
            }
        };
        $logger = new FakeLogger;
        $middleware = new ExecuteJavascriptMiddleware(
            $logger,
            static fn (string $uri): Browsershot => $throwingBrowsershot->setUrl($uri),
        );

        $middleware->handleResponse($this->makeResponse());

        self::assertTrue(
            $logger->messageWasLogged(
                'info',
                '[ExecuteJavascriptMiddleware] Error while executing javascript',
            ),
        );
    }

    public function test_uses_the_provided_user_agent_option(): void
    {
        $mockBrowserShot = $this->createMock(Browsershot::class);
        $response = $this->makeResponse(
            $this->makeRequest('http://localhost:8000/javascript'),
        );
        $middleware = new ExecuteJavascriptMiddleware(
            new FakeLogger,
            static fn (string $uri): Browsershot => $mockBrowserShot,
        );
        $middleware->configure(['userAgent' => 'custom']);

        $mockBrowserShot->expects(self::once())
            ->method('userAgent')
            ->with(self::equalTo('custom'));

        $middleware->handleResponse($response);
    }
}
