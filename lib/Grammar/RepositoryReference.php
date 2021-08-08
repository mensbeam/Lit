<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/**
 * Acts as a sort of lazy reference for repository items in grammars.
 */
class RepositoryReference extends Reference {
    protected ?Grammar $grammar;
    protected string $_name;
    protected PatternList|Pattern|null|false $object = null;


    public function __construct(string $name, Grammar $grammar) {
        $this->_name = $name;
        // Using a \WeakReference here doesn't work for some reason even though
        // the grammar would still be stored in memory. Cloning works because grammars
        // are immutable, so the referenced object never will change.
        $this->grammar = clone $grammar;
    }


    public function get(): PatternList|Pattern|null {
        if ($this->object !== null) {
            return $this->object;
        } elseif ($this->object === false) {
            return null;
        }

        $grammar = $this->grammar;
        if (!isset($grammar->repository[$this->name])) {
            $this->object = false;
            return null;
        }

        $this->object = $grammar->repository[$this->name];
        return $this->object;
    }
}