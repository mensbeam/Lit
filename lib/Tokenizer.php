<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\{
        Pattern,
        Reference,
        RepositoryReference
};
use dW\Lit\Scope\{
    Filter,
    Parser as ScopeParser
};


class Tokenizer {
    protected \Generator $data;
    protected Grammar $grammar;
    protected int $offset = 0;
    protected ?Pattern $activeInjection = null;
    protected array $ruleStack;
    protected array $scopeStack;
    protected int $debug = 0;


    public function __construct(\Generator $data, Grammar $grammar) {
        $this->data = $data;
        $this->grammar = $grammar;
        $this->ruleStack = [ $this->grammar ];
        $this->scopeStack = [ $this->grammar->scopeName ];
    }


    public function tokenize(): \Generator {
        foreach ($this->data as $lineNumber => $line) {
            $this->debug = $lineNumber;
            $this->offset = 0;
            $tokens = $this->tokenizeLine($line);

            // If after tokenizing the line the entire line still hasn't been tokenized then
            // create a token of the rest of the line.
            $lineLength = strlen($line);
            if ($this->offset < $lineLength) {
                $tokens[] = new Token(
                    $this->scopeStack,
                    substr($line, $this->offset, $lineLength)
                );
            }

            yield $lineNumber => $tokens;
        }
    }


    protected function resolveScopeName(string $scopeName, array $match): string {
        return preg_replace_callback('/\$(\d+)|\${(\d+):\/(downcase|upcase)}/', function($m) use ($match) {
            $replacement = $match[(int)$m[1]][0] ?? $m[1];
            $command = $m[2] ?? null;
            switch ($command) {
                case 'downcase': return strtolower($replacement);
                break;
                case 'upcase': return strtoupper($replacement);
                break;
                default: return $replacement;
            }
        }, $scopeName);
    }

    protected function tokenizeLine(string $line): array {
        $tokens = [];
        $lineLength = strlen($line);

        if ($this->activeInjection === null && $this->grammar->injections !== null) {
            foreach ($this->grammar->injections as $selector => $injection) {
                $selector = ScopeParser::parseSelector($selector);
                if ($selector->matches($this->scopeStack)) {
                    $prefix = $selector->getPrefix($this->scopeStack);
                    if ($prefix === Filter::PREFIX_LEFT || $prefix === Filter::PREFIX_BOTH) {
                        $this->scopeStack[] = $injection;
                        $this->activeInjection = $injection;
                        break;
                    }
                }
            }
        }

        while (true) {
            $currentRules = end($this->ruleStack)->patterns;
            $currentRulesCount = count($currentRules);

            for ($i = 0; $i < $currentRulesCount; $i++) {
                while (true) {
                    $rule = $currentRules[$i];

                    // If the rule is a Pattern and matches the line at the offset then tokenize the
                    // matches.
                    if ($rule instanceof Pattern && preg_match($rule->match, $line, $match, PREG_OFFSET_CAPTURE, $this->offset)) {
                        // ¡TEMPORARY! Haven't implemented begin and end line
                        // anchors, so let's toss them completely.
                        if (preg_match('/\\\(?:A|G)/', $rule->match)) {
                            continue 2;
                        }

                        // Add the name and contentName to the scope stack
                        // if present.
                        if ($rule->name !== null) {
                            $this->scopeStack[] = $this->resolveScopeName($rule->name, $match);
                        }
                        if ($rule->contentName !== null) {
                            $this->scopeStack[] = $this->resolveScopeName($rule->contentName, $match);
                        }

                        if ($rule->captures !== null) {
                            // Iterate through each of the matched subpatterns and create tokens from the
                            // captures.
                            foreach ($match as $k => $m) {
                                if ($m[0] === '') {
                                    continue;
                                }

                                // If the subpattern begins after the offset then create a token from the bits
                                // of the line in-between the last token and the one about to be created.
                                if ($m[1] > $this->offset) {
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

                                    $tokens[] = new Token(
                                        $scopeStack,
                                        substr($line, $this->offset, $m[1])
                                    );
                                    $this->offset = $m[1];
                                }

                                // The first match is the whole match, and if there are captures for it the name
                                // and contentName should be added to the stack regardless of whether it has
                                // patterns or not.
                                if ($k === 0) {
                                    if (!isset($rule->captures[0])) {
                                        continue;
                                    }

                                    if ($rule->captures[0]->name !== null) {
                                        $this->scopeStack[] = $this->resolveScopeName($rule->captures[0]->name, $match);
                                    }
                                    if ($rule->captures[0]->contentName !== null) {
                                        $this->scopeStack[] = $this->resolveScopeName($rule->captures[0]->contentName, $match);
                                    }
                                }

                                // If the capture rule has patterns of its own then
                                // those must be matched, too.
                                if ($rule->captures[$k]->patterns !== null) {
                                    $this->ruleStack[] = $rule->captures[$k];

                                    // The scope stack for the whole match is handled above, so only handle that for
                                    // other captures.
                                    if ($k > 0) {
                                        if ($rule->captures[$k]->name !== null) {
                                            $this->scopeStack[] = $this->resolveScopeName($rule->captures[$k]->name, $match);
                                        }
                                        if ($rule->captures[$k]->contentName !== null) {
                                            $this->scopeStack[] = $this->resolveScopeName($rule->captures[$k]->contentName, $match);
                                        }
                                    }

                                    $tokens = [ ...$tokens, ...$this->tokenizeLine($line) ];

                                    // The scope stack for the whole match is handled above, so only handle that for
                                    // other captures.
                                    if ($k > 0) {
                                        if ($rule->captures[$k]->contentName !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                        if ($rule->captures[$k]->name !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                    }

                                    array_pop($this->ruleStack);
                                } else {
                                    // If it's not the 0 capture and a capture without any patterns add the name
                                    // and content names if they exist to the token's scope stack but not to the
                                    // global one.
                                    $scopeStack = $this->scopeStack;
                                    if ($k > 0) {
                                        if ($rule->captures[$k]->name !== null) {
                                            $scopeStack[] = $this->resolveScopeName($rule->captures[$k]->name, $match);
                                        }
                                        if ($rule->captures[$k]->contentName !== null) {
                                            $scopeStack[] = $this->resolveScopeName($rule->captures[$k]->contentName, $match);
                                        }
                                    }

                                    $tokens[] = new Token(
                                        $scopeStack,
                                        $m[0]
                                    );
                                }

                                $this->offset = $m[1] + strlen($m[0]);
                                $firstCapture = false;
                            }
                        }

                        $this->ruleStack[] = $rule;

                        if ($rule->patterns !== null && $this->offset < $lineLength) {
                            $tokens = [ ...$tokens, ...$this->tokenizeLine($line) ];
                        }

                        if (!$rule->beginPattern) {
                            if ($rule->endPattern) {
                                while (!end($this->ruleStack)->beginPattern) {
                                    $popped = array_pop($this->ruleStack);

                                    if ($popped->captures !== null && isset($popped->captures[0])) {
                                        if ($popped->captures[0]->contentName !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                        if ($popped->captures[0]->name !== null) {
                                            array_pop($this->scopeStack);
                                        }
                                    }

                                    if ($popped->contentName !== null) {
                                        array_pop($this->scopeStack);
                                    }
                                    if ($popped->name !== null) {
                                        array_pop($this->scopeStack);
                                    }

                                    // If what was just popped is the active injection then remove it, too
                                    if ($popped === $this->activeInjection) {
                                        $this->activeInjection = null;
                                    }
                                }
                            }

                            $popped = array_pop($this->ruleStack);

                            if ($popped->captures !== null && isset($popped->captures[0])) {
                                if ($popped->captures[0]->contentName !== null) {
                                    array_pop($this->scopeStack);
                                }
                                if ($popped->captures[0]->name !== null) {
                                    array_pop($this->scopeStack);
                                }
                            }

                            if ($popped->contentName !== null) {
                                array_pop($this->scopeStack);
                            }
                            if ($popped->name !== null) {
                                array_pop($this->scopeStack);
                            }

                            // If what was just popped is the active injection then remove it, too.
                            if ($popped === $this->activeInjection) {
                                $this->activeInjection = null;
                            }
                        }

                        /*
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
                        $popped = array_pop($this->ruleStack);

                        // If what was just popped is the active injection then remove it, too.
                        if ($popped === $this->activeInjection) {
                            $this->activeInjection = null;
                        }*/

                        break 2;
                    }
                    // Otherwise, if the rule is a Reference then retrieve its patterns, splice into
                    // the rule list, and reprocess the rule.
                    elseif ($rule instanceof Reference && $obj = $rule->get()) {
                        if ($obj instanceof Grammar || ($rule instanceof RepositoryReference && $obj->match === null)) {
                            $obj = $obj->patterns;
                        }

                        array_splice($currentRules, $i, 1, $obj);
                        $currentRulesCount = count($currentRules);
                        continue;
                    }

                    break;
                }
            }

            if ($this->activeInjection === null && $this->grammar->injections !== null) {
                foreach ($this->grammar->injections as $selector => $injection) {
                    $selector = ScopeParser::parseSelector($selector);
                    if ($selector->matches($this->scopeStack) && $selector->getPrefix($this->scopeStack) !== Filter::PREFIX_LEFT) {
                        $this->ruleStack[] = $injection;
                        $this->activeInjection = $injection;

                        if ($this->offset < $lineLength) {
                            continue 2;
                        }
                    }
                }
            }

            break;
        }

        return $tokens;
    }
}