<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;

abstract class ImmutableList implements \ArrayAccess, \Countable, \Iterator {
    protected int $count = 0;
    protected int|string|null $position;
    protected array $storage = [];


    public function __construct(...$values) {
        $this->storage = $values;
        $this->count = count($this->storage);
    }

    // Used when adopting to change the $ownerGrammar property of items in the
    // list.
    public function withOwnerGrammar(Grammar $ownerGrammar): self {
        $new = clone $this;
        foreach ($new->storage as &$s) {
            $s = $s->withOwnerGrammar($ownerGrammar);
        }

        return $new;
    }


    public function count(): int {
        return $this->count;
    }

    public function current() {
        return current($this->storage);
    }

    public function getIterator(): array {
        return $this->storage;
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
