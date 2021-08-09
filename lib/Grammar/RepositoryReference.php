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
    protected string $_name;
    protected PatternList|Pattern|null|false $object = null;


    public function __construct(string $name, Grammar $ownerGrammar) {
        $this->_name = $name;
        parent::__construct($ownerGrammar);
    }


    public function get(): PatternList|Pattern|null {
        if ($this->object === false) {
            return null;
        } elseif ($this->object !== null) {
            return $this->object;
        }

        $grammar = $this->_ownerGrammar->get();
        if (!isset($grammar->repository)) {
            die(var_export($grammar));
        }

        if (!isset($grammar->repository[$this->name])) {
            $this->object = false;
            return null;
        }

        $this->object = $grammar->repository[$this->name];
        return $this->object;
    }
}