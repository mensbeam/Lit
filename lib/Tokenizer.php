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
    protected Grammar $grammar;
    protected array $ruleStack;
    protected array $scopeStack;


    public function __construct(\Generator $data, Grammar $grammar) {
        $this->data = $data;
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
        if (preg_match($regex, $line, $match, PREG_OFFSET_CAPTURE, $offset) !== 1) {
            return null;
        }

        return $match;
    }

    protected function _tokenize(string $inputLine, int $offset = 0): array {
        $currentRules = end($this->ruleStack)->patterns->getIterator();
        $currentRulesCount = count($currentRules);
        $results = [];
        $line = $inputLine;
        $lineLength = strlen($line);

        for ($i = 0; $i < $currentRulesCount; $i++) {
            while (true) {
                $rule = $currentRules[$i];
                if ($rule instanceof Pattern) {
                    if ($match = $this->getMatch($rule->match, $line, $offset)) {
                        $tokens = [];
                        unset($match[0]);
                        foreach ($match as $k => $m) {
                            if ($m[1] > $offset) {
                                $tokens[] = [
                                    'scope' => $this->scopeStack,
                                    'string' => substr($line, $offset, $m[1])
                                ];
                                $offset = $m[1];
                            }

                            $tokens[] = [
                                'scope' => [ ...$this->scopeStack, $rule->captures[$k]->name ],
                                'string' => $m[0]
                            ];
                            $offset = $m[1] + strlen($m[0]);
                        }

                        echo "\n";
                        die(var_export($tokens));
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