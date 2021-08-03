<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Composite extends Node {
    protected array $_expressions = [];


    public function __construct(Expression ...$expressions) {
        $this->_expressions = $expressions;
    }


    public function getPrefix(array $scopes): ?int {
        if ($this->matches($scopes)) {
            return $this->_expressions[0]->getPrefix($scopes);
        }
    }

    public function matches(array $scopes): bool {
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

            $local = $expression->matches($scopes);

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
