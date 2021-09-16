<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\Exception;


class Highlight {
    /**
     * Highlights incoming string data and outputs a PHP DOMElement.
     *
     * @param string $data - The input data string.
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data.
     * @param ?\DOMDocument $document = null - An existing DOMDocument to use as the owner document of the returned DOMElement; if omitted one will be created instead.
     * @param string $encoding = 'windows-1252' - The encoding of the input data string; only used if a document wasn't provided in the previous parameter, otherwise it uses the encoding of the existing DOMDocument
     * @return \DOMElement
     */
    public static function toElement(string $data, string $scopeName, ?\DOMDocument $document = null, string $encoding = 'windows-1252'): \DOMElement {
        $grammar = GrammarRegistry::get($scopeName);
        if ($grammar === false) {
            throw new Exception(Exception::GRAMMAR_MISSING, $scopeName);
        }

        $tokenizer = new Tokenizer(new Data($data), $grammar);
        $tokenList = $tokenizer->tokenize();

        if ($document === null) {
            $document = new \DOMDocument();
            $document->encoding = $encoding;
        }

        $pre = $document->createElement('pre');
        $code = $document->createElement('code');
        $code->setAttribute('class', self::scopeNameToCSSClassList($scopeName));
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
                        $span->setAttribute('class', self::scopeNameToCSSClassList($scope));
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

    /**
     * Highlights incoming string data and outputs a string containing serialized HTML.
     *
     * @param string $data - The input data string
     * @param string $scopeName - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data
     * @param string $encoding = 'windows-1252' - The encoding of the input data string
     * @return string
     */
    public static function toString(string $data, string $scopeName, string $encoding = 'windows-1252'): string {
        $pre = self::toElement($data, $scopeName, null, $encoding);
        return $pre->ownerDocument->saveHTML($pre);
    }


    protected static function scopeNameToCSSClassList(string $scopeName): string {
        return implode(' ', array_unique(explode('.', $scopeName)));
    }
}