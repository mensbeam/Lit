<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Selector extends Node {
    protected array $_composites = [];
    protected bool $frozen = false;


    public function __construct(?Group $parent = null) {
        $this->_parent = ($parent === null) ? null : \WeakReference::create($parent);
    }


    public function add(Composite ...$composites): bool {
        if ($this->frozen) {
            return false;
        }

        $this->_composites = $composites;
        $this->frozen = true;
        return true;
    }

    public function matches(Path|string $path, &$match = null): bool {
        if (is_string($selector)) {
            $path = Parser::parsePath($path);
        }

        foreach ($this->_composites as $composite) {
            if ($composite->matches($path)) {
                $match = $composite;
                return true;
            }
        }

        return false;
    }


    public function __toString(): string {
        return implode(', ', $this->_composites);
    }
}
