<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

use dW\Lit\Scope\Parser as ScopeParser,
    dW\Lit\Grammar\Pattern,
    dW\Lit\Grammar\RepositoryReference;

class Tokenizer {
    protected \Generator $data;
    protected Grammar $grammar;

    protected array $ruleStack;


    public function __construct(\Generator $data, Grammar $grammar) {
        $this->data = $data;
        $this->grammar = $grammar;
        $this->ruleStack = [ $this->grammar ];
    }


    public function tokenize(): \Generator {
        foreach ($this->data as $lineNumber => $line) {
            yield $lineNumber => $line;
        }
    }
}