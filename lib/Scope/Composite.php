<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Composite extends Node {
    protected array $_expressions = [];
    protected bool $frozen = false;


    public function __construct(Selector $parent) {
        $this->_parent = \WeakReference::create($parent);
    }


    public function add(Expression ...$expressions): bool {
        if ($this->frozen) {
            return false;
        }

        $this->_expressions = $expressions;
        $this->frozen = true;
        return true;
    }

    public function __toString(): string {
        return implode(' ', $this->_expressions);
    }
}
