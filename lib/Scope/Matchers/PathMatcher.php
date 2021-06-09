<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class PathMatcher extends Matcher {
    protected string|null $prefix;
    protected array $matchers;

    public function __construct(string|null $prefix, Matcher ...$matchers) {
        $this->prefix = ($prefix !== null) ? $prefix[0] : null;
        $this->matchers = $matchers;
    }
}
