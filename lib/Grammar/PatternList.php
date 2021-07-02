<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

/** Immutable list of pattern rules */
class PatternList extends ImmutableList {
    public function __construct(Pattern|GrammarInclude ...$values) {
        parent::__construct(...$values);
    }
}