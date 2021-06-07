<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class SegmentMatcher extends Matcher {
    protected string $segment;

    public function __construct(string $segment) {
        $this->segment = $segment;
    }
}
