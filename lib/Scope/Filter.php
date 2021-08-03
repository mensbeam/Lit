<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Filter extends Node {
    protected Group|Path $_child;
    protected int $_prefix;

    const PREFIX_LEFT = 0;
    const PREFIX_RIGHT = 1;
    const PREFIX_BOTH = 2;


    public function __construct(Group|Path $child, string $prefix) {
        $this->_child = $child;

        switch ($prefix) {
            case 'L': $this->_prefix = self::PREFIX_LEFT;
            break;
            case 'R': $this->_prefix = self::PREFIX_RIGHT;
            break;
            case 'B': $this->_prefix = self::PREFIX_BOTH;
            break;
        }
    }


    public function getPrefix(array $scopes): ?int {
        return ($this->matches($scopes)) ? $this->_prefix : null;
    }

    public function matches(array $scopes): bool {
        // TODO: Handle prefixes when matching; AFAIK prefixes only apply when
        // determining when to inject grammars. It appears there's more to it in
        // TextMate's original C++, but it may just be for determining "rank"?
        return $this->_child->matches($scopes);
    }


    public function __toString(): string {
        switch ($this->_prefix) {
            case self::PREFIX_LEFT: $prefix = 'L';
            break;
            case self::PREFIX_RIGHT: $prefix = 'R';
            break;
            case self::PREFIX_BOTH: $prefix = 'B';
            break;
        }

        return "$prefix:{$this->_child}";
    }
}
