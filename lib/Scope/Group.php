<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Group extends Node {
    protected Selector $_child;


    public function __construct(Selector $child) {
        $this->_child = $child;
    }


    public function getPrefix(array $scopes): ?int {
        return ($this->matches($scopes)) ? $this->_child->getPrefix($scopes) : null;
    }

    public function matches(array $scopes): bool {
        return $this->_child->matches($scopes);
    }


    public function __toString(): string {
        return "({$this->_child})";
    }
}
