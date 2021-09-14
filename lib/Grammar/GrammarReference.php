<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\{
    Grammar,
    GrammarRegistry
};


/**
 * Grammar references act as a placeholder for grammars in rule lists
 */
class GrammarReference extends Reference {
    protected string $_scopeName;


    public function __construct(string $scopeName, string $ownerGrammarScopeName) {
        $this->_scopeName = $scopeName;
        parent::__construct($ownerGrammarScopeName);
    }


    public function get(): Grammar|false {
        return GrammarRegistry::get($this->_scopeName);
    }
}