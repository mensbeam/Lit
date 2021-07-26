<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Expression extends Node {
    protected Filter|Group|Path $_child;
    protected bool $frozen = false;
    protected int $_operator;

    const OPERATOR_NONE = 0;
    const OPERATOR_AND = 1;
    const OPERATOR_OR = 2;
    const OPERATOR_NOT = 3;


    public function __construct(Composite $parent, int $operator = self::OPERATOR_NONE) {
        $this->_operator = $operator;
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


    public function __toString(): string {
        switch ($this->_operator) {
            case OPERATOR_NONE: $operator = '';
            break;
            case OPERATOR_AND: $operator = '& ';
            break;
            case OPERATOR_OR: $operator = '| ';
            break;
            case OPERATOR_NOT: $operator = '- ';
        }

        return "$operator${$this->_child}";
    }
}
