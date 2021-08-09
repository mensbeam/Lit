<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/**
 * Static storage for child grammars; a map of a scope string and a Grammar
 * object and checked against an owner grammar. Exists to prevent multiple clones
 * of the same grammar from being created and also to give weak references a
 * place in memory to access.
 */
class ChildGrammarRegistry {
    protected static array $storage = [];

    public static function clear(): bool {
        self::$storage = [];
        return true;
    }

    public static function get(string $scopeName, Grammar $ownerGrammar): ?Grammar {
        if (!array_key_exists($scopeName, self::$storage)) {
            return null;
        }

        $grammars = self::$storage[$scopeName];
        foreach ($grammars as $g) {
            if ($g->ownerGrammar === $ownerGrammar) {
                return $g;
            }
        }

        return null;
    }

    public static function set(string $scopeName, Grammar $grammar): bool {
        try {
            if (!array_key_exists($scopeName, self::$storage)) {
                self::$storage[$scopeName] = [ $grammar ];
                return true;
            }

            $grammars = self::$storage[$scopeName];
            foreach ($grammars as $key => $value) {
                if ($value->ownerGrammar === $grammar->ownerGrammar) {
                    return false;
                }
            }

            self::$storage[$scopeName][] = $grammar;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}