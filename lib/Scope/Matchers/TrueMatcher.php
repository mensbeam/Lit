<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class TrueMatcher extends Matcher {
    protected string $scopeName;

    public function __construct(string $scopeName) {
        $this->scopeName = $scopeName;
    }

    public function matches(string $scope): bool {
        return true;
    }

    public function getPrefix(string $scope): string|null|false {
        return null;
    }
}
