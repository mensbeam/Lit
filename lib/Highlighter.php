<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

class Highlighter {
    public static function highlightFile(string $filepath, string $scopeName) {
        $data = Data::fileToGenerator($filepath);
    }

    public static function highlightString(string $string, string $scopeName) {
        $data = Data::stringToGenerator($string);
    }
}