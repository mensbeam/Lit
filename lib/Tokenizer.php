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
        foreach ($this->data as $lineNumber => $inputLine) {
            $currentRules = end($this->ruleStack)->patterns->getIterator();
            $currentRulesCount = count($currentRules);
            $results = [];
            $line = $inputLine;

            for ($i = 0; $i < $currentRulesCount; $i++) {
                while (true) {
                    $rule = $currentRules[$i];
                    if ($rule instanceof Pattern) {
                        $regex = null;
                        if ($rule->match !== null) {
                            $regex = $rule->match;
                        } elseif ($rule->begin !== null) {
                            $regex = $rule->begin;
                        }

                        if ($regex !== null && $match = $this->getMatch($regex, $line)) {
                            $scopeStack = $this->scopeStack;
                            if ($rule->name !== null) {
                                $scopeStack[] = $rule->name;
                            }
                            if ($rule->contentName !== null) {
                                $scopeStack[] = $rule->contentName;
                            }

                            $results[] = [
                                'scopeStack' => $scopeStack,
                                'matches' => $match
                            ];

                            die(var_export($rule));

                            if ($rule->begin !== null) {
                                $this->ruleStack[] = $rule;
                                $this->scopeStack[] = $scopeStack;
                            }
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
        }
    }


    protected function getMatch(string $regex, string $line): ?array {
        if (preg_match($regex, $line, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return $match;
    }
}