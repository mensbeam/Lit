<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

class Data {
    public static function fileToGenerator(string $filepath): \Generator {
        $lineNumber = 0;
        $fp = fopen($filepath, 'r');
        try {
            while ($line = fgets($fp)) {
                yield ++$lineNumber => $line;
            }
        } finally {
            fclose($fp);
        }
    }

    public static function stringToGenerator(string $string): \Generator {
        $string = explode("\n", $string);
        foreach ($string as $lineNumber => $line) {
            yield $lineNumber + 1 => $line;
        }
    }
}