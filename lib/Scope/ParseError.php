<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class ParseError {
    const MESSAGE = 'Highlighter Scope Parse Error: %s expected; found %s at offset %s';

    public static function clearHandler() {
        restore_error_handler();
    }

    public static function errorHandler(int $code, string $message) {
        echo "$message\n";
    }

    public static function setHandler() {
        set_error_handler([__CLASS__, 'errorHandler'], \E_USER_ERROR);
    }

    public static function trigger(array|string $expected, string|bool $found, int $offset) {
        if (!is_string($expected)) {
            $expectedLen = count($expected);
            if ($expectedLen === 1) {
                $expected = ($expected[0] !== false) ? $expected[0] : 'end of input';
            } else {
                $expected = array_map(function($n) {
                    $n = ($n !== false) ? $n : 'end of input';
                }, $expected);

                if ($expectedLen > 2) {
                    $last = array_pop($expected);
                    $expected = implode(', ', $expected) . ', or ' . $last;
                } else {
                    $expected = implode(' or ', $expected);
                }
            }
        }

        $found = ($found !== false) ? "\"$found\"" : 'end of input';
        trigger_error(sprintf(self::MESSAGE, $expected, $found, $offset), \E_USER_ERROR);
        exit(1);
    }
}
