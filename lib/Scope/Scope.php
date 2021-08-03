<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Scope extends Node {
    protected bool $_anchorToPrevious;
    protected array $_atoms = [];
    protected ?\WeakReference $_parent;


    public function __construct(?Scope $parent = null, bool $anchorToPrevious = false, string ...$atoms) {
        $this->_anchorToPrevious = $anchorToPrevious;
        $this->_atoms = $atoms;
        $this->_parent = ($parent !== null) ? \WeakReference::create($parent) : null;
    }


    public function matches(Scope $scope): bool {
        foreach ($this->_atoms as $index => $atom) {
            if ($atom === '*') {
                continue;
            }

            if ($atom !== $scope->atoms[$index]) {
                return false;
            }
        }

        return true;
    }


    public function __get(string $name) {
        if ($name === 'parent') {
            return ($this->_parent !== null) ? $this->_parent->get() : null;
        }

        return parent::__get($name);
    }

    public function __toString(): string {
        $result = '';

        if ($this->_anchorToPrevious) {
            $result .= '< ';
        }

        $result .= implode('.', $this->_atoms);

        return $result;
    }
}
