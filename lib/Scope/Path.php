<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Path extends Node {
    protected int $_anchor;

    protected array $frozen = [
        'add' => false,
        'anchor' => false
    ];

    protected array $_scopes = [];

    const ANCHOR_NONE = 0;
    const ANCHOR_START = 1;
    const ANCHOR_END = 2;
    const ANCHOR_BOTH = 3;


    public function __construct(Expression $parent) {
        $this->_parent = \WeakReference::create($parent);
    }


    public function add(Scope ...$scopes): bool {
        if ($this->frozen['add']) {
            return false;
        }

        $this->_scopes = $scopes;
        $this->frozen['add'] = true;
        return true;
    }


    public function __set(string $name, $value) {
        if ($name !== 'anchor') {
            $trace = debug_backtrace();
            trigger_error("Cannot set undefined property $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
        }

        if ($this->frozen['anchor']) {
            $trace = debug_backtrace();
            trigger_error("Cannot set readonly $name property in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
            return;
        }

        $this->frozen['anchor'] = true;
        $this->_anchor = $value;
    }

    public function __toString(): string {
        $result = '';

        if ($this->_anchor === self::ANCHOR_START || $this->_anchor === self::ANCHOR_BOTH) {
            $result .= '^';
        }

        $result .= implode(' ', $this->_scopes);

        if ($this->_anchor === self::ANCHOR_END || $this->_anchor === self::ANCHOR_BOTH) {
            $result .= '$';
        }

        return $result;
    }
}
