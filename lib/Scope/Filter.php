<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Filter extends Node {
    protected Group|Path $_child;
    protected int $_prefix;

    const SIDE_LEFT = 0;
    const SIDE_RIGHT = 1;
    const SIDE_BOTH = 2;


    public function __construct(Group|Path $child, string $prefix) {
        $this->_child = $child;

        switch ($prefix) {
            case 'L': $this->_prefix = self::SIDE_LEFT;
            break;
            case 'R': $this->_prefix = self::SIDE_RIGHT;
            break;
            case 'B': $this->_prefix = self::SIDE_BOTH;
            break;
        }
    }


    public function matches(array $scopes): bool {
        // No idea if prefixes are supposed to affect matches. Appears to in the
        // TextMate original but not in Atom's implementation...
        return $this->_child->matches($scopes);
    }


    public function __toString(): string {
        switch ($this->_prefix) {
            case self::SIDE_LEFT: $prefix = 'L';
            break;
            case self::SIDE_RIGHT: $prefix = 'R';
            break;
            case self::SIDE_BOTH: $prefix = 'B';
            break;
        }

        return "$prefix:{$this->_child}";
    }
}
