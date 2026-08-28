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

namespace RoachPHP\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;

final class Client implements ClientInterface
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient;
    }

    /**
     * @param  list<Request>  $requests
     */
    public function pool(
        array $requests,
        ?callable $onFulfilled = null,
        ?callable $onRejected = null,
    ): void {
        $makeRequests = function () use ($requests): \Generator {
            foreach ($requests as $request) {
                yield fn () => $this->client->sendAsync($request->getPsrRequest(), $request->getOptions());
            }
        };

        $pool = new Pool($this->client, $makeRequests(), [
            'concurrency' => 0,
            'fulfilled' => static function (ResponseInterface $response, int|string $index) use ($requests, $onFulfilled): void {
                if ($onFulfilled === null || ! is_int($index) || ! isset($requests[$index])) {
                    return;
                }

                $onFulfilled(new Response($response, $requests[$index]));
            },
            'rejected' => static function (mixed $reason, int|string $index) use ($requests, $onFulfilled, $onRejected): void {
                if (! is_int($index) || ! isset($requests[$index])) {
                    if ($reason instanceof \Throwable) {
                        throw $reason;
                    }

                    throw new \RuntimeException('Request rejected with a non-throwable reason.');
                }

                $request = $requests[$index];

                if ($reason instanceof BadResponseException) {
                    if ($onFulfilled !== null) {
                        $onFulfilled(new Response($reason->getResponse(), $request));
                    }

                    return;
                }

                if (! $reason instanceof GuzzleException) {
                    if ($reason instanceof \Throwable) {
                        throw $reason;
                    }

                    throw new \RuntimeException('Request rejected with a non-throwable reason.');
                }

                if ($onRejected !== null) {
                    $onRejected(new RequestException($request, $reason));
                }
            },
        ]);

        $pool->promise()->wait();
    }
}
