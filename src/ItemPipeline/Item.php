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

namespace RoachPHP\ItemPipeline;

use RoachPHP\Support\Droppable;

final class Item implements ItemInterface
{
    use Droppable;

    public function __construct(private array $data) {}

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): ItemInterface
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return (\is_int($offset) || \is_string($offset)) && isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! \is_int($offset) && ! \is_string($offset)) {
            throw new \InvalidArgumentException('Offset needs to be an integer or string');
        }

        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! \is_int($offset) && ! \is_string($offset)) {
            throw new \InvalidArgumentException('Offset needs to be an integer or string');
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (! \is_int($offset) && ! \is_string($offset)) {
            throw new \InvalidArgumentException('Offset needs to be an integer or string');
        }

        unset($this->data[$offset]);
    }
}
