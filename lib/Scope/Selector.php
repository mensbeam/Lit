<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Selector extends Node {
    protected array $composites = [];
    protected bool $frozen = false;


    public function __construct(?Group $parent = null) {
        $this->_parent = ($parent === null) ? null : \WeakReference::create($parent);
    }


    public function add(Composite ...$composites): bool {
        if ($this->frozen) {
            return false;
        }

        $this->composites = $this->composites;
        $this->frozen = true;
        return true;
    }
}
