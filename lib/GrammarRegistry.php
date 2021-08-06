<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

/** Static storage for grammars; a map of a scope string and a Grammar object */
class GrammarRegistry implements \IteratorAggregate {
    protected static array $storage = [];

    public static function clear(): bool {
        self::$storage = [];
        return true;
    }

    public static function delete(string $scopeName): bool {
        try {
            unset(self::$storage[$scopeName]);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public static function get(string $scopeName): Grammar|bool {
        if (array_key_exists($scopeName, self::$storage)) {
            return self::$storage[$scopeName];
        } else {
            $filename = __DIR__ . "/../data/$scopeName.json";
            if (file_exists($filename)) {
                $grammar = new Grammar();
                $grammar->loadJSON($filename);
                return $grammar;
            }
        }

        return false;
    }

    public function getIterator(): \Traversable {
        foreach (self::$storage as $scopeName => $grammar) {
            yield $scopeName => $grammar;
        }
    }

    public static function has(string $scopeName): bool {
        return (array_key_exists($scopeName, self::$storage));
    }

    public static function keys(): \Traversable {
        foreach (self::$storage as $scopeName => $_) {
            yield $scopeName;
        }
    }

    public static function set(string $scopeName, Grammar $grammar): bool {
        try {
            self::$storage[$scopeName] = $grammar;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function values(): \Traversable {
        foreach (self::$storage as $grammar) {
            yield $grammar;
        }
    }
}