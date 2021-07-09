<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

class Tokenizer {
    protected \Generator $data;
    protected Grammar $grammar;


    public function __construct(\Generator $data, Grammar $grammar) {
        $this->data = $data;
        $this->grammar = $grammar;
    }


    public function tokenize(): \Generator {
        $ruleStack = [ $this->grammar ];

        foreach ($this->data as $lineNumber => $line) {


            yield $lineNumber => $line;
        }
    }
}