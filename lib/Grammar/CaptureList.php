<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

class CaptureList extends ImmutableList {
    public function __construct(array $array) {
        foreach ($array as $k => $v) {
            assert(is_int($k), new \Exception('Integer index expected for supplied array, found ' . gettype($k) . "\n"));
            assert($v instanceof GrammarInclude || $v instanceof Pattern || $v instanceof PatternList, new \Exception(__NAMESPACE__ . '\GrammarInclude, ' . __NAMESPACE__ . '\Pattern, or ' . __NAMESPACE__ . '\PatternList value expected for supplied array, found ' . gettype($v) . "\n"));
        }

        $this->storage = $array;
    }
}