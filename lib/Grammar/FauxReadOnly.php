<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;


trait FauxReadOnly {
    public function __get(string $name) {
        $prop = "_$name";
        $exists = property_exists($this, $prop);
        if ($name === 'ownerGrammar' && $exists) {
            return $this->_ownerGrammar->get();
        }

        if (!$exists) {
            $trace = debug_backtrace();
            trigger_error("Cannot get undefined property $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);
            return null;
        }

        return $this->$prop;
    }
}