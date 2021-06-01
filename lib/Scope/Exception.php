<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Exception extends \Exception {
    const MESSAGE = '%s expected; found %s';

    public function __construct(array|string $expected, string $found) {
        if (is_array($expected)) {
            $expected = array_map(function($n) {
                return ($n !== false) ? "\"$n\"" : 'end of input';
            }, $expected);

            if (count($expected) > 2) {
                $last = array_pop($expected);
                $expected = implode(', ', $expected) . ', or ' . $last;
            } else {
                $expected = implode(' or ', $expected);
            }
        } else {
            $expected = ($expected !== false) ? "\"$expected\"" : 'end of input';
        }

        $found = ($found !== false) ? "\"$found\"" : 'end of input';
        parent::__construct(sprintf(self::MESSAGE, $expected, $found), 2112);
    }
}
