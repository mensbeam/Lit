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
    protected Data $data;
    protected Grammar $grammar;
    protected int $offset = 0;
    protected ?Pattern $activeInjection = null;
    protected array $ruleStack;
    protected array $scopeStack;
    protected int $debug = 0;
    protected int $debugCount = 0;

    protected const SCOPE_RESOLVE_REGEX = '/\$(\d+)|\${(\d+):\/(downcase|upcase)}/S';
    protected const ANCHOR_CHECK_REGEX = '/(?<!\\\)\\\([AGzZ])/S';


    public function __construct(Data $data, Grammar $grammar) {
        $this->data = $data;
        $this->grammar = $grammar;
        $this->ruleStack = [ $this->grammar ];
        $this->scopeStack = [ $this->grammar->scopeName ];
    }


    public function tokenize(): \Generator {
        foreach ($this->data->get() as $lineNumber => $line) {
            $this->debug = $lineNumber;
            $this->debugCount = 0;
            $this->offset = 0;

            $lineLength = strlen($line);
            $tokens = ($lineLength > 0) ? $this->tokenizeLine($line) : [];

            // Output a token for everything else contained on the line including the
            // newline or just a newline if there weren't any spare characters left on the
            // line. If it is the last line, and there's nothing else remaining on the line
            // then output no additional token.
            if ($this->offset < $lineLength) {
                $tokens[] = new Token(
                    $this->scopeStack,
                    substr($line, $this->offset, $lineLength - $this->offset) . ((!$this->data->lastLine) ? "\n" : '')
                );
                $this->debugCount++;
            } elseif (!$this->data->lastLine) {
                $tokens[] = new Token(
                    $this->scopeStack,
                    "\n"
                );
                $this->debugCount++;
            }

            yield $lineNumber => $tokens;
        }
    }


    protected function resolveScopeName(string $scopeName, array $match): string {
        return preg_replace_callback(self::SCOPE_RESOLVE_REGEX, function($m) use($match) {
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

        while (true) {
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

            $currentRules = end($this->ruleStack)->patterns;
            $currentRulesCount = count($currentRules);
            $closestMatch = null;

            // Iterate through the rules to find matches for the line at the current offset.
            for ($i = 0; $i < $currentRulesCount; $i++) {
                while (true) {
                    $rule = $currentRules[$i];

                    if ($this->debug === 19 && $this->debugCount === 3) {
                        $rule->get();
                        die(var_export($rule->ownerGrammar));
                    }

                    // If the rule is a Pattern
                    if ($rule instanceof Pattern) {
                        // Throw out pattern regexes with anchors that should match the current line.
                        // This is necessary because the tokenizer is fed data line by line.
                        if (preg_match(self::ANCHOR_CHECK_REGEX, $rule->match, $validRegexMatch) === 1) {
                            if (
                                // \A anchors match the beginning of the whole string, not just this line
                                ($validRegexMatch[1] === 'A' && !$this->data->firstLine) ||
                                // \z anchors match the end of the whole string, not just this line
                                ($validRegexMatch[1] === 'z' && !$this->data->lastLine) ||
                                // \Z anchors match the end of the whole string or before the final newline if
                                // there's a trailing newline in the string
                                ($validRegexMatch[1] === 'Z' && !$this->data->lastLineBeforeFinalNewLine)
                            ) {
                                continue 2;
                            }
                        }

                        if (preg_match($rule->match, "$line\n", $match, PREG_OFFSET_CAPTURE, $this->offset)) {
                            // If the match's offset is the same as the current offset then it is the
                            // closest match. There's no need to iterate anymore through the patterns.
                            if ($match[0][1] === $this->offset) {
                                $closestMatch = [
                                    'match' => $match,
                                    'pattern' => $rule
                                ];
                                break 2;
                            }
                            // Otherwise, if the closest match is currently null or the match's offset is
                            // less than the closest match's offset then set the match as the closest match
                            // and continue looking for a closer one.
                            elseif ($closestMatch === null || $match[0][1] < $closestMatch['match'][0][1]) {
                                $closestMatch = [
                                    'match' => $match,
                                    'pattern' => $rule
                                ];
                            }
                        }
                    }
                    // Otherwise, if the rule is a Reference then retrieve its patterns, splice into
                    // the rule list, and reprocess the rule.
                    elseif ($rule instanceof Reference && $obj = $rule->get()) {
                        if ($obj instanceof Grammar || ($rule instanceof RepositoryReference && $obj->match === null)) {
                            $obj = $obj->patterns;
                        }

                        array_splice($currentRules, $i, 1, ($obj instanceof Pattern) ? [ $obj ] : $obj);
                        $currentRulesCount = count($currentRules);
                        continue;
                    }

                    break;
                }
            }

            // If there were a match above...
            if ($closestMatch !== null) {
                $match = $closestMatch['match'];
                $pattern = $closestMatch['pattern'];

                // If the subpattern begins after the offset then create a token from the bits
                // of the line in-between the last token and the one(s) about to be created.
                if ($match[0][1] > $this->offset) {
                    $tokens[] = new Token(
                        $this->scopeStack,
                        substr($line, $this->offset, $match[0][1] - $this->offset)
                    );
                    $this->debugCount++;
                    $this->offset = $match[0][1];
                }

                // Add the name to the scope stack if present.
                if ($pattern->name !== null) {
                    $this->scopeStack[] = $this->resolveScopeName($pattern->name, $match);
                }

                // If a rule has captures iterate through each of the matched subpatterns and
                // create tokens from the captures.
                if ($pattern->captures !== null) {
                    foreach ($match as $k => $m) {
                        if ($m[0] === '' || ($k === 0 && !isset($pattern->captures[0]))) {
                            continue;
                        }

                        // If the capture begins after the offset then create a token from the bits of
                        // the line in-between the last token and the one(s) about to be created.
                        if ($k > 0 && $m[1] > $this->offset) {
                            $tokens[] = new Token(
                                $this->scopeStack,
                                substr($line, $this->offset, $m[1] - $this->offset)
                            );
                            $this->debugCount++;
                            $this->offset = $m[1];
                        }

                        // If the capture has a name add it to the scope stack.
                        if ($pattern->captures[$k]->name !== null) {
                            $this->scopeStack[] = $this->resolveScopeName($pattern->captures[$k]->name, $match);
                        }

                        // If the capture has patterns of its own add the capture to the rule stack,
                        // process the patterns, and then pop the capture off the stack.
                        if ($pattern->captures[$k]->patterns !== null) {
                            $this->ruleStack[] = $pattern->captures[$k];
                            $tokens = [ ...$tokens, ...$this->tokenizeLine($line) ];

                            // If the offset is before the end of the capture then create a token from the
                            // bits of the capture from the offset until the end of the capture.
                            $endOffset = $m[1] + strlen($m[0]);
                            if ($endOffset > $this->offset) {
                                $tokens[] = new Token(
                                    $this->scopeStack,
                                    substr($line, $this->offset, $endOffset - $this->offset)
                                );
                                $this->debugCount++;
                                $this->offset = $endOffset;
                            }

                            array_pop($this->ruleStack);
                        }
                        // Otherwise, create a token for the capture.
                        else {
                            $tokens[] = new Token(
                                $this->scopeStack,
                                $m[0]
                            );
                            $this->debugCount++;
                        }

                        // Pop the capture's name off the scope stack.
                        if ($pattern->captures[$k]->name !== null) {
                            array_pop($this->scopeStack);
                        }

                        $this->offset = $m[1] + strlen($m[0]);
                    }
                }
                // Otherwise, if the rule doesn't have captures then a token is created from the
                // entire match, but only if the matched text isn't empty.
                elseif ($match[0][0] !== '') {
                    $tokens[] = new Token(
                        $this->scopeStack,
                        $match[0][0]
                    );

                    $this->offset = $match[0][1] + strlen($match[0][0]);
                    $this->debugCount++;
                }

                // If the pattern is a begin pattern and has a content name then add that to the
                // scope stack before processing the children.
                if ($pattern->beginPattern && $pattern->contentName !== null) {
                    $this->scopeStack[] = $this->resolveScopeName($pattern->contentName, $match);
                }

                $this->ruleStack[] = $pattern;

                // If the rule has patterns process tokens from its subpatterns.
                if ($pattern->patterns !== null && $this->offset < $lineLength) {
                    $tokens = [ ...$tokens, ...$this->tokenizeLine($line) ];
                }

                // If the offset is before the end of the match then create a token from the
                // bits of the match from the offset until the end of the match.
                $endOffset = $match[0][1] + strlen($match[0][0]);
                if ($endOffset > $this->offset) {
                    $tokens[] = new Token(
                        $this->scopeStack,
                        substr($line, $this->offset, $endOffset - $this->offset)
                    );
                    $this->debugCount++;
                    $this->offset = $endOffset;
                }

                if (!$pattern->beginPattern) {
                    if ($pattern->endPattern) {
                        while (!end($this->ruleStack)->beginPattern) {
                            $popped = array_pop($this->ruleStack);

                            if ($popped->name !== null) {
                                array_pop($this->scopeStack);
                            }

                            // If what was just popped is the active injection then remove it, too.
                            if ($popped === $this->activeInjection) {
                                $this->activeInjection = null;
                            }
                        }
                    }

                    $popped = array_pop($this->ruleStack);

                    // If what was just popped is a begin pattern and has a content name pop it off
                    // the scope stack.
                    if ($popped->beginPattern && $popped->contentName !== null) {
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

                // If the offset isn't at the end of the line then look for more matches.
                if ($this->offset !== $lineLength) {
                    continue;
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