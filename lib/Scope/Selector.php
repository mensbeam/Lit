<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Scope;

class Selector extends Node {
    protected array $_composites = [];


    public function __construct(Composite ...$composites) {
        $this->_composites = $composites;
    }


    public function getPrefix(array $scopes): ?int {
        $matches = $this->matches($scopes, $match);
        return ($matches) ? $match->getPrefix($scopes) : null;
    }

    public function matches(array $scopes, ?Composite &$match = null): bool {
        foreach ($this->_composites as $composite) {
            if ($composite->matches($scopes)) {
                $match = $composite;
                return true;
            }
        }

        return false;
    }


    public function __toString(): string {
        return implode(', ', $this->_composites);
    }
}
