<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

abstract class ImmutableList implements \ArrayAccess, \Countable {
    protected $storage = [];
    protected $count = 0;

    public function __construct(...$values) {
        $this->storage = $values;
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

    public function count(): int {
        return $this->count;
    }
}
