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

    protected function _tokenize(string $line, int &$offset = 0): array {
        $tokens = [];
        $lineLength = strlen($line);

        while (true) {
            $currentRules = end($this->ruleStack)->patterns->getIterator();
            $currentRulesCount = count($currentRules);

            for ($i = 0; $i < $currentRulesCount; $i++) {
                while (true) {
                    $rule = $currentRules[$i];
                    // If the rule is a Pattern and matches the line at the offset then tokenize the
                    // matches.
                    if ($rule instanceof Pattern && $match = $this->getMatch($rule->match, $line, $offset)) {
                        // First, remove the first entry in the match, the full
                        // match, leaving only the subpatterns.
                        //unset($match[0]);

                        // Add the name and contentName to the scope stack
                        // if present.
                        if ($rule->name !== null) {
                            $this->scopeStack[] = $rule->name;
                        }
                        if ($rule->contentName !== null) {
                            $this->scopeStack[] = $rule->contentName;
                        }

                        $wholeMatchCaptureScopeCount = 0;
                        if ($rule->captures !== null) {
                            // Iterate through each of the matched subpatterns and create tokens from the
                            // captures.
                            foreach ($match as $k => $m) {
                                if ($m[0] === '') {
                                    continue;
                                }

                                // If the subpattern begins after the offset then create a token from the bits
                                // of the line in-between.
                                if ($m[1] > $offset) {
                                    $scopeStack = $this->scopeStack;
                                    // If this is the first capture, then the scopes added to the stack need to be
                                    // removed from this token's scope stack as this will grab everything before
                                    // this match began.
                                    if ($k === 0) {
                                        if ($rule->contentName !== null) {
                                            array_pop($scopeStack);
                                        }
                                        if ($rule->name !== null) {
                                            array_pop($scopeStack);
                                        }
                                    }

                                    $tokens[] = [
                                        'scopes' => $scopeStack,
                                        'string' => substr($line, $offset, $m[1])
                                    ];
                                    $offset = $m[1];
                                }

                                // The first match is the whole match, and if there are captures for it the name
                                // and contentName should be added to the stack regardless of whether it has
                                // patterns or not. However, keep count of how many were added to the stack so
                                // they may be removed when this rule has finished tokenizing.
                                if ($k === 0) {
                                    if (!isset($rule->captures[0])) {
                                        continue;
                                    }

                                    if ($rule->captures[0]->name !== null) {
                                        $this->scopeStack[] = $rule->captures[0]->name;
                                        $wholeMatchCaptureScopeCount++;
                                    }
                                    if ($rule->captures[0]->contentName !== null) {
                                        $this->scopeStack[] = $rule->captures[0]->contentName;
                                        $wholeMatchCaptureScopeCount++;
                                    }
                                }

                                // If the capture rule has patterns of its own then
                                // those must be matched, too.
                                if ($rule->captures[$k]->patterns !== null) {
                                    $this->ruleStack[] = $rule->captures[$k];

                                    // The scope stack for the whole match is handled above, so only handle that for
                                    // other captures.
                                    if ($k !== 0) {
                                        if ($rule->captures->name !== null) {
                                            $this->scopeStack[] = $rule->captures[$k]->name;
                                        }
                                        if ($rule->captures->contentName !== null) {
                                            $this->scopeStack[] = $rule->captures[$k]->contentName;
                                        }
                                    }

                                    $tokens = [ ...$tokens, ...$this->_tokenize($line, $offset) ];

                                    // The scope stack for the whole match is handled above, so only handle that for
                                    // other captures.
                                    if ($k !== 0) {
                                        if ($rule->captures[$k]->contentName !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                        if ($rule->captures[$k]->name !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                    }

                                    array_pop($this->ruleStack);
                                } else {
                                    $tokens[] = [
                                        'scopes' => [ ...$this->scopeStack, $rule->captures[$k]->name ],
                                        'string' => $m[0]
                                    ];
                                }

                                $offset = $m[1] + strlen($m[0]);
                                $firstCapture = false;
                            }
                        }

                        if ($rule->patterns !== null) {
                            $tokens = [ ...$tokens, ...$this->_tokenize($line, $offset) ];
                        }

                        // Remove the name and contentName from the scope stack if present.
                        if ($rule->contentName !== null) {
                            array_pop($this->scopeStack);
                        }
                        if ($rule->name !== null) {
                            array_pop($this->scopeStack);
                        }

                        // If the rule has a whole match capture (0) then remove its name and
                        // contentName, too.
                        $j = 0;
                        while ($j++ < $wholeMatchCaptureScopeCount) {
                            array_pop($this->scopeStack);
                        }

                        // And remove the rule from the rule stack, too.
                        array_pop($this->ruleStack);

                        echo "\n";
                        die(var_export($tokens));
                        break 2;
                    }
                    // Otherwise, if the rule is a Reference then retrieve its patterns, splice into
                    // the rule list, and reprocess the rule.
                    elseif ($rule instanceof Reference && $obj = $rule->get()) {
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

            break;
        }

        return $tokens;
    }
}