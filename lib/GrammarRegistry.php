<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit;


/** Static storage for grammars; a map of a scope string and a Grammar object */
class GrammarRegistry {
    protected static array $storage = [];


    /** Clears all grammars from the registry */
    public static function clear() {
        self::$storage = [];
    }

    /**
     * Retrieves a grammar from the registry
     *
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that is being requested
     * @return MensBeam\Lit\Grammar|false
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
     * Retrieves whether a grammar exists in the registry or not.
     *
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that is being requested
     * @return bool
     */
    public static function has(string $scopeName): bool {
        $result = array_key_exists($scopeName, self::$storage);
        return $result ?: file_exists(__DIR__ . "/../data/$scopeName.json");
    }

    /**
     * Sets a grammar in the registry.
     *
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that is being set
     * @param MensBeam\Lit\Grammar - The grammar to be put into the registry
     */
    public static function set(string $scopeName, Grammar $grammar) {
        self::$storage[$scopeName] = $grammar;
    }
}