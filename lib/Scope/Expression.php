<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Scope;

class Expression extends Node {
    protected Filter|Group|Path $_child;
    protected bool $_negate = false;
    protected int $_operator;

    const OPERATOR_NONE = 0;
    const OPERATOR_AND = 1;
    const OPERATOR_OR = 2;
    const OPERATOR_NOT = 3;


    public function __construct(Filter|Group|Path $child, int $operator = self::OPERATOR_NONE, bool $negate = false) {
        $this->_child = $child;
        $this->_negate = $negate;
        $this->_operator = $operator;
    }


    public function getPrefix(array $scopes): ?int {
        return ($this->matches($scopes)) ? $this->_child->getPrefix($scopes) : null;
    }

    public function matches(array $scopes): bool {
        $matches = $this->_child->matches($scopes);
        return ($this->_negate) ? !$matches : $matches;
    }


    public function __toString(): string {
        switch ($this->_operator) {
            case self::OPERATOR_NONE: $operator = '';
            break;
            case self::OPERATOR_AND: $operator = '& ';
            break;
            case self::OPERATOR_OR: $operator = '| ';
            break;
            case self::OPERATOR_NOT: $operator = '- ';
        }

        $negate = ($this->_negate) ? '- ' : '';
        return "$operator$negate{$this->_child}";
    }
}
