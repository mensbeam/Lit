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


    public function count(): int {
        return $this->count;
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

    public function offsetExists($offset) {
        return isset($this->storage[$offset]);
    }

    public function offsetGet($offset) {
        if (!isset($this->storage[$offset])) {
            throw new Exception(Exception::LIST_INVALID_INDEX, __CLASS__, $offset);
        }

        return $this->storage[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new Exception(Exception::LIST_IMMUTABLE, __CLASS__);
    }

    public function offsetUnset($offset) {
        throw new Exception(Exception::LIST_IMMUTABLE, __CLASS__);
    }

    public function rewind() {
        reset($this->storage);
        $this->position = key($this->storage);
    }

    public function valid() {
        return $this->offsetExists($this->position);
    }
}
