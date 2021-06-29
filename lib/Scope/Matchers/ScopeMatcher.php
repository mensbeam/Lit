<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class ScopeMatcher extends Matcher {
    protected array $segments;

    public function __construct(string ...$segments) {
        $this->segments = $segments;
    }

    public function matches(string $scope): bool {
        $scopeSegments = explode('.', $scope);

        if (count($this->segments) !== count($scopeSegments)) {
            return false;
        }

        foreach ($this->segments as $index => $segment) {
            if ($segment === '*') {
                continue;
            }

            if ($segment !== $scopeSegments[$index]) {
                return false;
            }
        }

        return true;
    }

    public function getPrefix(string $scope) {
        return null;
    }
}
