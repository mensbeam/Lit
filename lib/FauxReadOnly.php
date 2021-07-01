<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

trait FauxReadOnly {
    public function __get(string $name) {
        if ($name[0] === '_') {
            return;
        }

        $prop = "_$name";
        return $this->$prop;
    }
}