<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

class Registry {
    protected static array $grammars = [];

    public static function clear(): bool {
        self::$grammars = [];
        return true;
    }

    public static function delete(string $scopeName): bool {
        try {
            unset(self::$grammars[$scopeName]);
        } catch (Exception $e) {
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

    public static function import(string $jsonPath, bool $force = false): bool {
        if (!file_exists($jsonPath)) {
            throw new \Exception("Path \"$jsonPath\" either does not exist or you do not have permission to view the file.");
        }

        $grammar = json_decode(file_get_contents($jsonPath), true);
        if ($grammar === null) {
            throw new \Exception("\"$jsonPath\" is not a valid grammar JSON file.");
        }

        if (!isset($grammar['scopeName'])) {
            throw new \Exception("\"$jsonPath\" is missing the required scopeName property.");
        }

        if (!$force && isset(self::$grammars[$grammar['scopeName']])) {
            throw new \Exception("Grammar with the \"{$grammar['scopeName']}\" scope already exists.");
        }

        self::$grammars[$grammar['scopeName']] = $grammar;
        return true;
    }
}