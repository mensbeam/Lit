<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class NegateMatcher extends Matcher {
    public function __construct(Matcher $groupOrPath) {}
}
