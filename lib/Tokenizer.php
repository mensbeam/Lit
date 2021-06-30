<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

class Tokenizer {
    protected \Generator $data;

    public function __constructor(\Generator $data) {
        $this->data = $data;
    }

    public function tokenize(): \Generator {
        foreach ($this->data as $lineNumber => $line) {

        }
    }
}