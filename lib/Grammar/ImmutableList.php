<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

abstract class ImmutableList implements \ArrayAccess, \Countable, \Iterator {
    protected int $count = 0;
    protected int|string|null $position;
    protected array $storage = [];

    public function __construct(...$values) {
        $this->storage = $values;
        $this->count = count($this->storage);
    }

    public function offsetSet($offset, $value) {
        throw new \Exception(__CLASS__ . "s are immutable\n");
    }

    public function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }

    public function offsetUnset($offset) {
        throw new \Exception(__CLASS__ . "s are immutable\n");
    }

    public function offsetGet($offset) {
        assert(isset($this->storage[$offset]), new \Exception("Invalid ImmutableList index at $offset\n"));
        return $this->storage[$offset];
    }

    public function rewind() {
        reset($this->storage);
        $this->position = key($this->storage);
    }

    public function current() {
        return current($this->storage);
    }

    public function key(){
        $this->position = key($this->storage);
        return $this->position;
    }

    public function next() {
        next($this->storage);
        $this->position = key($this->storage);
    }

    public function valid() {
        return $this->offsetExists($this->position);
    }

    public function count(): int {
        return $this->count;
    }
}
