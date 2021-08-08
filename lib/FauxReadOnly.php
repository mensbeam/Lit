<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;


trait FauxReadOnly {
    public function __get(string $name) {
        $prop = "_$name";
        if (!property_exists($this, $prop)) {
            $trace = debug_backtrace();
            trigger_error("Cannot get undefined property $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
            return null;
        }

        return $this->$prop;
    }
}