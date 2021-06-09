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
}
