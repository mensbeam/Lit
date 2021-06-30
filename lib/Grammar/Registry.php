<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

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

    public static function validate(string $grammar): bool {
        if ($grammar === null) {
            throw new \Exception("\"$jsonPath\" is not a valid grammar JSON file.".\PHP_EOL);
        }

        $requiredProperties = [
            'name',
            'patterns',
            'scopeName'
        ];

        $missing = [];
        foreach ($requiredProperties as $r) {
            if (!array_key_exists($r, $grammar))) {
                $missing = $r;
            }
        }

        $missingLen = count($missing);
        if ($missingLen > 0) {
            if ($missingLen > 1) {
                if ($missingLen > 2) {
                    $last = array_pop($missing);
                    $missing = implode(', ', $missing);
                    $missing .= ", and $last";
                } else {
                    $missing = implode(' and ', $missing);
                }

                throw new \Exception("\"$jsonPath\" is missing the required $missing properties.".\PHP_EOL);
            }

            throw new \Exception("\"$jsonPath\" is missing the required {$missing[0]} property.".\PHP_EOL);
        }

        return true;
    }
}