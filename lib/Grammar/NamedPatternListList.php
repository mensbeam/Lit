<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

abstract class NamedPatternListList extends ImmutableList {
    public function __construct(array $array) {
        foreach ($array as $k => $v) {
            assert(is_string($k), new \Exception('String index expected for supplied array, found ' . gettype($k) . "\n"));
            assert($v instanceof PatternList, new \Exception(__NAMESPACE__ . '\PatternList value expected for supplied array, found ' . gettype($v) . "\n"));
        }

        $this->storage = $array;
    }
}