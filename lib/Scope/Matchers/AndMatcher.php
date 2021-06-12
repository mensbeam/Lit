<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class AndMatcher extends Matcher {
    protected Matcher $left;
    protected Matcher $right;

    public function __construct(Matcher $left, Matcher $right) {
        $this->left = $left;
        $this->right = $right;
    }

    public function matches(string ...$scopes): bool {
        return ($this->left->matches(...$scopes) && $this->right->matches(...$scopes));
    }

    public function getPrefix(string ...$scopes): string|null|false {
        if ($this->left->matches(...$scopes) && $this->right->matches(...$scopes)) {
            return $this->left->getPrefix(...$scopes);
        }
    }
}
