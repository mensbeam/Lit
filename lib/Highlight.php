<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\Exception;


class Highlight {
    public static function withFile(string $filepath, string $scopeName, string $encoding = 'UTF-8') {
        return self::highlight(Data::fileToGenerator($filepath, $encoding), $scopeName, $encoding);
    }

    public static function withString(string $string, string $scopeName, string $encoding = 'UTF-8') {
        return self::highlight(Data::stringToGenerator($string, $encoding), $scopeName, $encoding);
    }


    protected static function highlight(\Generator $data, string $scopeName, string $encoding) {
        $grammar = GrammarRegistry::get($scopeName);
        if ($grammar === false) {
            throw new Exception(Exception::GRAMMAR_MISSING, $scopeName);
        }

        mb_regex_encoding('UTF-32');

        $tokenizer = new Tokenizer($data, $grammar, $encoding);
        $tokenList = $tokenizer->tokenize();

        foreach ($tokenList as $lineNumber => $line) {
            echo "$lineNumber: $line\n";
        }

        mb_regex_encoding();
    }
}