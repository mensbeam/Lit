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

    const MATCH_MODE_SINGLE = 0;
    const MATCH_MODE_BEGINEND = 1;


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
        foreach ($this->data as $lineNumber => $line) {
            yield $lineNumber => $this->tokenizeLine($line);
        }
    }


    protected function getMatch(string $regex, string $line): ?array {
        if (preg_match($regex, $line, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return $match;
    }

    protected function tokenizeLine(string $inputLine): array {
        $currentRules = end($this->ruleStack)->patterns->getIterator();
        $currentRulesCount = count($currentRules);
        $results = [];
        $line = $inputLine;

        for ($i = 0; $i < $currentRulesCount; $i++) {
            while (true) {
                $rule = $currentRules[$i];
                if ($rule instanceof Pattern) {
                    $matchMode = null;
                    $regex = null;
                    if ($rule->match !== null) {
                        $regex = $rule->match;
                        $matchMode = self::MATCH_MODE_SINGLE;
                    } elseif ($rule->begin !== null) {
                        $regex = $rule->begin;
                        $matchMode = self::MATCH_MODE_BEGINEND;
                    }

                    if ($matchMode !== null && $match = $this->getMatch($regex, $line)) {
                        $scopeStack = $this->scopeStack;
                        if ($rule->name !== null) {
                            $scopeStack[] = $rule->name;
                        }
                        if ($rule->contentName !== null) {
                            $scopeStack[] = $rule->contentName;
                        }

                        die(var_export($rule));

                        if ($matchMode === self::MATCH_MODE_BEGINEND) {
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