<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Scope\Parser as ScopeParser;
use dW\Lit\Grammar\{
        Pattern,
        Reference
};


class Tokenizer {
    protected \Generator $data;
    protected string $encoding;
    protected Grammar $grammar;
    protected array $ruleStack;
    protected array $scopeStack;


    public function __construct(\Generator $data, Grammar $grammar, string $encoding) {
        $this->data = $data;
        $this->encoding = $encoding;
        $this->grammar = $grammar;
        $this->ruleStack = [ $this->grammar ];
        $this->scopeStack = [ $this->grammar->scopeName ];

        if ($this->grammar->contentScopeName !== null) {
            $this->scopeStack[] = $this->grammar->contentScopeName;
        }
    }


    public function tokenize(): \Generator {
        $appendNewLine = true;
        foreach ($this->data as $lineNumber => $inputLine) {
            yield $lineNumber => $this->_tokenize($inputLine);
            /*$line = $inputLine;
            $lineWithNewLine = ($appendNewLine) ? "$line\n" : $line;
            $initialStackRuleLength = count($this->ruleStack);
            $position = 0;
            $tokenCount = 0;

            while (true) {
                $initialStackRuleLength = count($this->ruleStack);
                $previousPosition = $position;
                if ($position > mb_strlen($line)) {
                    break;
                }
            }*/
        }
    }


    protected function getMatch(string $regex, string $line, int $offset = 0): ?array {
        // Using mbstring's regular expressions because it truly supports multibyte
        // strings but also because the original implementation used Oniguruma.
        mb_ereg_search_init($line, mb_convert_encoding($regex, 'UTF-32'));

        if ($offset !== 0) {
            // UTF-32 uses 4 bytes for every character; multiply by 4 to convert from
            // character offset to byte offset.
            mb_ereg_search_setpos($offset * 4);
        }

        $pos = mb_ereg_search_pos();
        if ($pos === false) {
            return null;
        }

        // UTF-32 uses 4 bytes for every character; divide by 4 to get character
        // offsets.
        $length = $pos[1] / 4;
        $pos = [
            'start' => $pos[0] / 4,
        ];
        $pos['end'] = $pos['start'] + $length;

        $match = mb_ereg_search_getregs();
        // Convert the matches back to the original encoding.
        foreach ($match as &$m) {
            $m = mb_convert_encoding($m, $this->encoding, 'UTF-32');
        }

        $match['offset'] = $pos;
        return $match;
    }

    protected function _tokenize(string $inputLine, int $offset = 0): array {
        $currentRules = end($this->ruleStack)->patterns->getIterator();
        $currentRulesCount = count($currentRules);
        $results = [];
        $line = $inputLine;

        for ($i = 0; $i < $currentRulesCount; $i++) {
            while (true) {
                $rule = $currentRules[$i];
                if ($rule instanceof Pattern) {
                    if ($match = $this->getMatch($rule->match, $line, $offset)) {
                        $offset = $match['offset']['end'];
                    }
                } elseif ($rule instanceof Reference && $obj = $rule->get()) {
                    if ($obj instanceof PatternList) {
                        $obj = $obj->getIterator();
                    } elseif ($obj instanceof Grammar) {
                        $obj = $obj->patterns->getIterator();
                    }

                    array_splice($currentRules, $i, 1, $obj);
                    $currentRulesCount = count($currentRules);
                    continue;
                }

                break;
            }
        }

        return $inputLine;
    }
}