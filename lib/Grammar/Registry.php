<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Grammar;

class Registry {
    protected static array $grammars = [];

    public static function add(string $grammarPath): bool {
        if (!file_exists($grammarPath)) {
            throw new \Exception("Path \"$grammarPath\" either does not exist or you do not have permission to view the file.");
        }

        if (isset(self::$grammars[$grammarPath])) {
            return true;
        }

        $grammar = json_decode(file_get_contents($grammarPath), true);
        if ($grammar === null) {
            throw new \Exception("\"$grammarPath\" is not a valid grammar file.");
        }

        self::$grammars[$grammarPath] = $grammar;
        return true;
    }
}