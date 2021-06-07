<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class ScopeMatcher extends Matcher {
    protected array $segments;

    public function __construct(SegmentMatcher|TrueMatcher ...$matchers) {
        $this->segments = $matchers;
    }
}
