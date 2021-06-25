<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class AndMatcher extends Matcher {
    protected array $matchers = [];

    public function __construct(Matcher ...$matchers) {
        $this->matchers = $matchers;
    }

    public function add(Matcher $matcher) {
        $this->matchers[] = $matcher;
    }

    public function matches(string ...$scopes): bool {
        foreach ($this->matchers as $m) {
            if (!$m->matches(...$scopes)) {
                return false;
            }
        }

        return true;
    }

    public function getPrefix(string ...$scopes): string|null|false {
        if ($this->matches(...$scopes)) {
            return $this->matches[0]->getPrefix(...$scopes);
        }

        return null;
    }
}
