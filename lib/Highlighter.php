<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Fukkus;

class Highlighter {
    public static function highlightString(string $string): string {
        $data = Grammar\Data::fromString($string);

    }
}