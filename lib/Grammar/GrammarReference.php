<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/**
 * Acts as a sort of lazy reference for entire grammars in grammars.
 */
class GrammarReference extends Reference {
    protected ?Grammar $object;
    protected \WeakReference $ownerGrammar;
    protected string $_scopeName;


    public function __construct(string $scopeName, Grammar $ownerGrammar) {
        $this->ownerGrammar = \WeakReference::create($ownerGrammar);
        $this->_scopeName = $scopeName;
    }


    public function get(): Grammar {
        if ($this->object !== null) {
            return $this->object;
        } elseif ($this->object === false) {
            return null;
        }

        $grammar = Registry::get($this->_scopeName);
        if ($grammar === null) {
            $this->object = false;
            return null;
        }

        $this->object = $this->ownerGrammar->get()->adopt($grammar);
        return $this->object;
    }
}