<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Group extends Node {
    protected Selector $_child;
    protected bool $frozen = false;


    public function __construct(Expression|Filter $parent) {
        $this->_parent = \WeakReference::create($parent);
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
