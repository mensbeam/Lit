<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class GroupMatcher extends Matcher {
    protected string|null $prefix;
    protected Matcher $selector;

    public function __construct(string|null $prefix, Matcher $selector) {
        $this->prefix = ($prefix !== null) ? $prefix[0] : null;
        $this->selector = $selector;
    }

    public function matches(array $scopes): bool {
        return $this->selector->matches($scopes);
    }

    public function getPrefix(array $scopes): string|null|false {
        if ($this->selector->matches($scopes)) {
            return $this->prefix;
        }
    }
}
