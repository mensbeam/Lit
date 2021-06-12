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

    public function matches(string ...$scopes): bool {
        $count = 0;
        $matcher = $this->matchers[$count];
        foreach ($scopes as $scope) {
            if ($matcher->matches($scope)) {
                 $matcher = $this->matchers[++$count];
            }
            if ($matcher === null) {
                return true;
            }
        }
        return false;
    }

    public function getPrefix(string ...$scopes): string|null|false {
        if ($this->matches($scopes)) {
            return $this->prefix;
        }
    }
}
