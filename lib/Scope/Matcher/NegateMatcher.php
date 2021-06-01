<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope\Matcher;

class NegateMatcher extends dW\Highlighter\Scope\Matcher {
    public function __construct(Matcher $groupOrPath) {}
}
