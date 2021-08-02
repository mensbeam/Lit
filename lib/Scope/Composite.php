<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Composite extends Node {
    protected array $_expressions = [];
    protected bool $frozen = false;


    public function __construct(Selector $parent) {
        $this->_parent = \WeakReference::create($parent);
    }


    public function add(Expression ...$expressions): bool {
        if ($this->frozen) {
            return false;
        }

        $this->_expressions = $expressions;
        $this->frozen = true;
        return true;
    }

    public function matches(Path $path): bool {
        $result = false;
        foreach ($this->_expressions as $expression) {
            $operator = $expression->operator;
            if ($result && $operator === Expression::OPERATOR_OR) {
                continue;
            } elseif (!$result && $operator === Expression::OPERATOR_AND) {
                continue;
            } elseif (!$result && $operator === Expression::OPERATOR_NOT) {
                continue;
            }

            $local = $expression->child->matches($path);
            if ($expression->negate) {
                $local = !$local;
            }

            switch ($operator) {
                case Expression::OPERATOR_NONE: $result = $local;
                break;
                case Expression::OPERATOR_OR: $result = $result || $local;
                break;
                case Expression::OPERATOR_AND: $result = $result && $local;
                break;
                case Expression::OPERATOR_NOT: $result = $result && !$local;
                break;
            }
        }

        return $result;
    }


    public function __toString(): string {
        return implode(' ', $this->_expressions);
    }
}
