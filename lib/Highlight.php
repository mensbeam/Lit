<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\Exception;
use MensBeam\HTML\{
        Document,
        Element
};


class Highlight {
    public static function toDOM(string $data, string $scopeName, ?Document $document = null, string $encoding = 'windows-1252'): Element {
        return self::highlight($data, $scopeName, $document, $encoding);
    }

    public static function toString(string $data, string $scopeName, string $encoding = 'windows-1252'): string {
        return (string)self::highlight($data, $scopeName, null, $encoding);
    }


    protected static function highlight(string $data, string $scopeName, ?Document $document = null, string $encoding = 'windows-1252'): Element {
        $grammar = GrammarRegistry::get($scopeName);
        if ($grammar === false) {
            throw new Exception(Exception::GRAMMAR_MISSING, $scopeName);
        }

        $tokenizer = new Tokenizer(new Data($data), $grammar);
        $tokenList = $tokenizer->tokenize();

        if ($document === null) {
            $document = new Document();
            $document->encoding = $encoding;
        }

        $pre = $document->createElement('pre');
        $code = $document->createElement('code');
        $code->setAttribute('class', str_replace('.', ' ', $scopeName));
        $pre->appendChild($code);

        $elementStack = [ $code ];
        $scopeStack = [ $scopeName ];

        foreach ($tokenList as $lineNumber => $tokens) {
            continue;
            foreach ($tokens as $token) {
                $lastKey = count($token['scopes']) - 1;
                foreach ($token['scopes'] as $key => $scope) {
                    $keyExists = array_key_exists($key, $scopeStack);
                    if (!$keyExists || $scopeStack[$key] !== $scope) {
                        if ($keyExists && $scopeStack[$key] !== $scope) {
                            $scopeStack = array_slice($scopeStack, 0, $key);
                            $elementStack = array_slice($elementStack, 0, $key);
                        }

                        $span = $document->createElement('span');
                        $span->setAttribute('class', str_replace('.', ' ', $scope));
                        end($elementStack)->appendChild($span);
                        $scopeStack[] = $scope;
                        $elementStack[] = $span;
                    }

                    if ($key === $lastKey) {
                        if (array_key_exists($key + 1, $scopeStack)) {
                            $scopeStack = array_slice($scopeStack, 0, $key + 1);
                            $elementStack = array_slice($elementStack, 0, $key + 1);
                        }

                        end($elementStack)->appendChild($document->createTextNode($token['text']));
                    }
                }
            }
        }

        return $pre;
    }
}