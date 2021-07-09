<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

class CaptureList extends ImmutableList {
    public function __construct(array $array) {
        /* This shit is here because PHP doesn't have array types or generics :) */
        foreach ($array as $k => $v) {
            if (!is_int($k)) {
                throw new Exception(Exception::LIST_INVALID_TYPE, 'Integer', 'supplied array index', gettype($k));
            }

            if (!$v instanceof GrammarInclude && !$v instanceof Rule && !$v instanceof RuleList) {
                throw new Exception(Exception::LIST_INVALID_TYPE,  __NAMESPACE__.'\GrammarInclude, '.__NAMESPACE__.'\Rule, or '.__NAMESPACE__.'\RuleList', 'supplied array value', gettype($v));
            }
        }

        $this->storage = $array;
    }
}