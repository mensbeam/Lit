<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar,
    dW\Lit\GrammarRegistry;


/**
 * Acts as a sort of lazy reference for entire grammars in grammars.
 */
class GrammarReference extends Reference {
    protected ?Grammar $object = null;
    protected string $_scopeName;


    public function __construct(string $scopeName, Grammar $ownerGrammar) {
        $this->_scopeName = $scopeName;
        parent::__construct($ownerGrammar);
    }


    public function get(): Grammar {
        if ($this->object !== null) {
            return $this->object;
        } elseif ($this->object === false) {
            return null;
        }

        $grammar = GrammarRegistry::get($this->_scopeName);
        if ($grammar === null) {
            $this->object = false;
            return null;
        }

        $this->object = $this->_ownerGrammar->get()->adoptGrammar($grammar);
        return $this->object;
    }
}