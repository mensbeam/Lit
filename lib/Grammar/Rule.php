<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/**
 * Abstract class used as a base class for Pattern and Reference classes
 */
abstract class Rule {
    use FauxReadOnly;
    protected \WeakReference $_ownerGrammar;


    public function __construct(Grammar $ownerGrammar) {
        $this->_ownerGrammar = \WeakReference::create($ownerGrammar);
    }

    // Used when adopting to change the $ownerGrammar property.
    public function withOwnerGrammar(Grammar $ownerGrammar): self {
        $new = clone $this;
        $new->_ownerGrammar = \WeakReference::create($ownerGrammar);
        return $new;
    }
}