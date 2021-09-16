<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;


/** Static storage for grammars; a map of a scope string and a Grammar object */
class GrammarRegistry {
    protected static array $storage = [];


    /**
     * Clears all grammars from the registry
     *
     * @return bool
     */
    public static function clear(): bool {
        self::$storage = [];
        return true;
    }

    /**
     * Retrieves a grammar from the registry
     *
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that is being requested
     * @return dW\Lit\Grammar|false
     */
    public static function get(string $scopeName): Grammar|false {
        if (array_key_exists($scopeName, self::$storage)) {
            return self::$storage[$scopeName];
        } else {
            $filename = __DIR__ . "/../data/$scopeName.json";
            if (file_exists($filename)) {
                $grammar = new Grammar();
                $grammar->loadJSON($filename);
                self::$storage[$scopeName] = $grammar;
                return $grammar;
            }
        }

        return false;
    }

    /**
     * Sets a grammar in the registry.
     *
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that is being set
     * @param dW\Lit\Grammar - The grammar to be put into the registry
     * @return bool
     */
    public static function set(string $scopeName, Grammar $grammar): bool {
        self::$storage[$scopeName] = $grammar;
        return true;
    }
}