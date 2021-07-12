<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly,
    dW\Lit\Grammar;

/**
 * Acts as a sort of lazy reference for repository items in grammars.
 */
class RepositoryReference extends Reference {
    use FauxReadOnly;

    protected \WeakReference $grammar;
    protected string $_name;
    protected PatternList|Pattern|null|false $object;


    public function __construct(string $name, Grammar $grammar) {
        $this->_name = $name;
        $this->grammar = \WeakReference::create($grammar);
    }


    public function get(): PatternList|Pattern {
        if ($this->object !== null) {
            return $this->object;
        } elseif ($this->object === false) {
            return null;
        }

        $grammar = $this->grammar->get();
        if (!isset($grammar->repository[$this->name])) {
            $this->object = false;
            return null;
        }

        $this->object = $grammar->repository[$this->name];
        return $this->object;
    }
}