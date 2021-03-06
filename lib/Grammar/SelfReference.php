<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Grammar;
use MensBeam\Lit\{
    Grammar,
    GrammarRegistry
};

/** A reference to a grammar's self. */
class SelfReference extends Reference {
    public function get(): Grammar {
        return GrammarRegistry::get($this->_ownerGrammarScopeName);
    }
}