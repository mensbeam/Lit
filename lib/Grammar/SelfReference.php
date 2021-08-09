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
    public function __construct(Grammar $grammar) {
        parent::__construct($grammar);
    }


    public function get(): Grammar {
        return $this->_ownerGrammar->get();
    }
}