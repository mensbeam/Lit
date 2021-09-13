<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\Exception;


class Highlight {
    public static function toDOM(string $data, string $scopeName) {
        self::highlight($data, $scopeName);
    }


    protected static function highlight(string $data, string $scopeName) {
        $grammar = GrammarRegistry::get($scopeName);
        if ($grammar === false) {
            throw new Exception(Exception::GRAMMAR_MISSING, $scopeName);
        }

        $tokenizer = new Tokenizer(new Data($data), $grammar);
        $tokenList = $tokenizer->tokenize();

        foreach ($tokenList as $lineNumber => $tokens) {
        }
    }
}