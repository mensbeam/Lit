<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;

/**
 * A weak reference to a grammar's self. This indeed doesn't have to exist, but
 * exists to maintain sanity when checking types.
 */
class SelfReference extends Reference {
    protected ?Grammar $grammar;


    public function __construct(Grammar $grammar, Grammar $ownerGrammar) {
        $this->grammar = $grammar;
        parent::__construct($ownerGrammar);
    }

    public function __destruct() {
        parent::__destruct();
        $this->grammar = null;
    }


    public function get(): Grammar {
        return $this->grammar;
    }
}