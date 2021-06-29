<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class NegateMatcher extends Matcher {
    protected Matcher $matcher;

    public function __construct(Matcher $matcher) {
        $this->matcher = $matcher;
    }

    public function matches(string ...$scopes): bool {
        return !($this->matcher->matches(...$scopes));
    }

    public function getPrefix(string ...$scopes) {
        return null;
    }
}
