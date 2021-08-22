<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\GrammarRegistry;


/**
 * Repository references act as a placeholder for named repository patterns in
 * rule lists
 */
class RepositoryReference extends Reference {
    protected string $_name;


    public function __construct(string $name, string $ownerGrammarScopeName) {
        $this->_name = $name;
        parent::__construct($ownerGrammarScopeName);
    }


    public function get(): ?Pattern {
        $grammar = GrammarRegistry::get($this->_ownerGrammarScopeName);
        if ($grammar->repository === null) {
            return null;
        }

        return (isset($grammar->repository[$this->_name])) ? $grammar->repository[$this->_name] : null;
    }
}