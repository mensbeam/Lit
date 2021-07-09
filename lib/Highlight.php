<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\Exception,
    dW\Lit\Grammar\Registry as GrammarRegistry;

class Highlight {
    public static function withFile(string $filepath, string $scopeName) {
        return self::highlight(Data::fileToGenerator($filepath), $scopeName);
    }

    public static function withString(string $string, string $scopeName) {
        return self::highlight(Data::stringToGenerator($string), $scopeName);
    }


    protected static function highlight(\Generator $data, string $scopeName) {
        $grammar = GrammarRegistry::get($scopeName);
        if ($grammar === false) {
            throw new Exception(Exception::GRAMMAR_MISSING, $scopeName);
        }

        $tokenizer = new Tokenizer($data, $grammar);
        $tokenList = $tokenizer->tokenize();

        foreach ($tokenList as $lineNumber => $line) {
            echo "$lineNumber: $line\n";
        }
    }
}