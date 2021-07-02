<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

/** Static storage for grammars; a map of scope and a Grammar object */
class Registry {
    protected static array $grammars = [];

    public static function clear(): bool {
        self::$grammars = [];
        return true;
    }

    public static function delete(string $scopeName): bool {
        try {
            unset(self::$grammars[$scopeName]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public static function get(string $scopeName): array|bool {
        foreach (self::$grammars as $grammar) {
            if ($grammar['scopeName'] === $scopeName) {
                return $grammar;
            }
        }

        return false;
    }
}