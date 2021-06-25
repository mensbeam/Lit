<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Fukkus\Scope;

class OrMatcher extends Matcher {
    protected array $matchers = [];

    public function __construct(Matcher ...$matchers) {
        $this->matchers = $matchers;
    }

    public function add(Matcher $matcher) {
        $this->matchers[] = $matcher;
    }

    public function matches(string ...$scopes): bool {
        foreach ($this->matchers as $m) {
            if ($m->matches(...$scopes)) {
                return true;
            }
        }

        return false;
    }

    public function getPrefix(string ...$scopes): string|null|false {
        foreach ($this->matchers as $m) {
            $prefix = $m->getPrefix(...$scopes);
            if ($prefix !== null) {
                return $prefix;
            }
        }

        return null;
    }
}
