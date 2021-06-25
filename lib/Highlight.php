<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit;

class Highlight {
    public static function withFile(string $filename): string {
        $data = Grammar\Data::withFile($filename);
    }

    public static function withString(string $string): string {
        $data = Grammar\Data::withString($string);
    }
}