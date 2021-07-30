<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Filter extends Node {
    protected Group|Path $_child;
    protected bool $frozen = false;
    protected int $_side;

    const SIDE_LEFT = 0;
    const SIDE_RIGHT = 1;
    const SIDE_BOTH = 2;


    public function __construct(Expression $parent, string $side) {
        $this->_parent = \WeakReference::create($parent);

        switch ($side) {
            case 'L': $this->_side = self::SIDE_LEFT;
            break;
            case 'R': $this->_side = self::SIDE_RIGHT;
            break;
            case 'B': $this->_side = self::SIDE_BOTH;
            break;
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


    public function __toString(): string {
        switch ($this->_side) {
            case self::SIDE_LEFT: $side = 'L';
            break;
            case self::SIDE_RIGHT: $side = 'R';
            break;
            case self::SIDE_BOTH: $side = 'B';
            break;
        }

        return "$side:{$this->_child}";
    }
}
