<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

class Data {
    public static function withFile(string $filepath): \Generator {
        $fp = fopen($filepath, 'r');
        try {
            while ($line = fgets($fp)) {
                yield $line;
            }
        } finally {
            fclose($fp);
        }
    }

    public static function withString(string $data): \Generator {
        $data = explode("\n", $data);
        foreach ($data as $d) {
            yield $d;
        }
    }
}