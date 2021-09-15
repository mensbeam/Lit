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
    /**
     * Highlights incoming string data and outputs an HTML DOM Mensbeam\HTML\Element.
     *
     * @param string $data - The input data string.
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data.
     * @param ?Mensbeam\HTML\Document [$document = null] - An existing MensBeam\HTML\Document to use as the owner document of the returned MensBeam\HTML\Element; if omitted one will be created instead.
     * @param string [$encoding = 'windows-1252'] - If a document isn't provided an encoding may be provided for the new document; the HTML standard default windows-1252 is used if no encoding is provided.
     * @return Mensbeam\HTML\Element
     */
    public static function toElement(string $data, string $scopeName, ?Document $document = null, string $encoding = 'windows-1252'): Element {
        return self::highlight($data, $scopeName, $document, $encoding);
    }

    /**
     * Highlights incoming string data and outputs an HTML string.
     *
     * @param string $data - The input data string.
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data.
     * @param string [$encoding = 'windows-1252'] - Encoding for the input string data; the HTML standard default windows-1252 is used if no encoding is provided.
     * @return string
     */
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
        $code->setAttribute('class', implode(' ', array_unique(explode('.', $scopeName))));
        $pre->appendChild($code);

        $elementStack = [ $code ];
        $scopeStack = [ $scopeName ];

        foreach ($tokenList as $lineNumber => $tokens) {
            foreach ($tokens as $token) {
                $lastKey = count($token['scopes']) - 1;
                foreach ($token['scopes'] as $key => $scope) {
                    $keyExists = array_key_exists($key, $scopeStack);
                    if (!$keyExists || $scopeStack[$key] !== $scope) {
                        if ($keyExists) {
                            $scopeStack = array_slice($scopeStack, 0, $key);
                            $elementStack = array_slice($elementStack, 0, $key);
                        }

                        $span = $document->createElement('span');
                        $span->setAttribute('class', implode(' ', array_unique(explode('.', $scope))));
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