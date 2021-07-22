<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

class Exception extends \Exception {
    const MESSAGE = '%s expected; found %s at offset %s'.\PHP_EOL;

    public function __construct(array|string $expected, string|bool $found, int $offset) {
        if (!is_string($expected)) {
            $expectedLen = count($expected);
            if ($expectedLen === 1) {
                $expected = ($expected[0] !== false) ? $expected[0] : 'end of input';
            } else {
                $temp = [];
                for ($i = 0; $i < $expectedLen; $i++) {
                    $temp[] = ($expected[$i] !== false) ? "{$expected[$i]}" : 'end of input';
                }
                $expected = $temp;

                if ($expectedLen > 2) {
                    $last = array_pop($expected);
                    $expected = implode(', ', $expected) . ', or ' . $last;
                } else {
                    $expected = implode(' or ', $expected);
                }
            }
        } else {
            $expected = ($expected !== false) ? $expected : 'end of input';
        }

        $found = ($found !== false) ? "\"$found\"" : 'end of input';
        parent::__construct(sprintf(self::MESSAGE, $expected, $found, $offset), 2112);
    }
}