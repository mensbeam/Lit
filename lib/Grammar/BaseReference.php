<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;

/**
 * Acts as a sort of lazy weak reference for a base grammar in a grammar.
 */
class BaseReference extends Reference {
    protected \WeakReference $grammar;
    protected ?\WeakReference $object;


    public function __construct(Grammar $grammar) {
        $this->grammar = \WeakReference::create($grammar);
    }


    public function get(): Grammar {
        if ($this->object !== null) {
            return $this->object->get();
        }

        $grammar = $this->grammar->get();
        do {
            $result = $grammar;
        } while ($grammar = $grammar->ownerGrammar);

        $this->object = $result;
        return $result->get();
    }
}