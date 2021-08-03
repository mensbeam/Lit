<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Selector extends Node {
    protected array $_composites = [];


    public function __construct(Composite ...$composites) {
        $this->_composites = $composites;
    }


    public function matches(array $scopes, &$match = null): bool {
        foreach ($scopes as &$s) {
            $isString = is_string($s);
            if (!$isString && !$s instanceof Scope) {
                throw new \Exception("Argument \$scopes must be an array containing only Scopes and/or strings.\n");
            }

            if ($isString) {
                $s = Parser::parseScope($s);
            }
        }

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
