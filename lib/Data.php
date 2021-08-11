<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;


class Data {
    public static function fileToGenerator(string $filepath, string $encoding = 'UTF-8'): \Generator {
        $lineNumber = 0;
        $fp = fopen($filepath, 'r');
        try {
            while ($line = fgets($fp)) {
                // Lines are converted to UTF-32 because everything in UTF-32 is 4 bytes, making
                // converting byte offsets to character offsets as easy as dividing by 4.
                yield ++$lineNumber => mb_convert_encoding($line, 'UTF-32', $encoding);
            }
        } finally {
            fclose($fp);
        }
    }

    public static function stringToGenerator(string $string, string $encoding = 'UTF-8'): \Generator {
        $string = explode("\n", $string);
        foreach ($string as $lineNumber => $line) {
            // Lines are converted to UTF-32 because everything in UTF-32 is 4 bytes, making
            // converting byte offsets to character offsets as easy as dividing by 4.
            yield $lineNumber + 1 => mb_convert_encoding($line, 'UTF-32', $encoding);
        }
    }
}