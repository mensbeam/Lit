<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Exception extends \Exception {
    const MESSAGE = '%s expected; found %s';

    public function __construct(string $expected, string $found) {
        $strlen = strlen($expected);
        if ($strlen > 1) {
            $temp = [];
            for ($i = 0; $i < $strlen; $i++) {
                $temp[] = ($expected[$i] !== false) ? "\"{$expected[$i]}\"" : 'end of input';
            }
            $expected = $temp;

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
