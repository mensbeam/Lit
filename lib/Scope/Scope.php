<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Scope extends Node {
    protected bool $_anchorToPrevious;
    protected array $atoms = [];
    protected bool $frozen = false;


    public function __construct(Path $parent, bool $anchorToPrevious = false) {
        $this->_anchorToPrevious = $anchorToPrevious;
        $this->_parent = \WeakReference::create($parent);
    }


    public function add(string ...$atoms): bool {
        if ($this->frozen) {
            return false;
        }

        $this->atoms = $atoms;
        $this->frozen = true;
        return true;
    }
}
