<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Expression extends Node {
    protected Filter|Group|Path $_child;
    protected bool $frozen = false;
    protected bool $_negate = false;
    protected ?string $_operator;


    public function __construct(Composite $parent, ?string $operator = null) {
        $this->_operator = $operator;
        $this->_parent = \WeakReference::create($parent);

        if ($operator === '-') {
            $this->negate = true;
        }
    }


    public function __set(string $name, $value) {
        if ($name !== 'child') {
            $trace = debug_backtrace();
            trigger_error("Cannot set undefined property $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
        }

        if ($this->frozen) {
            $trace = debug_backtrace();
            trigger_error("Cannot set readonly $name property in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
            return;
        }

        $this->frozen = true;
        $this->_child = $value;
    }
}
