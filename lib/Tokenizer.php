<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\{
        BaseReference,
        Pattern,
        Reference,
        RepositoryReference
};
use dW\Lit\Scope\{
    Filter,
    Parser as ScopeParser
};


class Tokenizer {
    public static bool $debug = false;

    protected Data $data;
    protected Grammar $grammar;
    protected int $offset = 0;
    protected bool $activeInjection = false;
    protected string $line = '';
    protected int $lineNumber = 1;
    // Cache of rule lists which have had references spliced to keep from having to
    // repeatedly splice in the same reference. It needs to be in two arrays because
    // PHP doesn't have a functioning Map object; the index needs to be an array
    // itself.
    protected array $ruleCacheIndexes = [];
    protected array $ruleCacheValues = [];
    protected array $ruleStack;
    protected array $scopeStack;

    protected const SCOPE_RESOLVE_REGEX = '/\$(\d+)|\${(\d+):\/(downcase|upcase)}/S';
    protected const ANCHOR_CHECK_REGEX = '/(?<!\\\)\\\([AGZz])/S';


    public function __construct(Data $data, Grammar $grammar) {
        $this->data = $data;
        $this->grammar = $grammar;
        $this->ruleStack = [ $this->grammar ];
        $this->scopeStack = [ $this->grammar->scopeName ];
    }


    public function tokenize(): \Generator {
        foreach ($this->data->get() as $lineNumber => $line) {
            $this->lineNumber = $lineNumber;
            $this->line = $line;

            // Because of how this tokenizes if the final line is just a new line it will
            // yield an empty token set; just end the generator instead.
            if ($this->data->lastLine && $line === '') {
                return;
            }

            assert($this->debugLine());
            $this->offset = 0;

            $lineLength = strlen($line);
            $tokens = ($lineLength > 0) ? $this->tokenizeLine($lineLength) : [];

            // Output a token for everything else contained on the line including the
            // newline or just a newline if there weren't any spare characters left on the
            // line. If it is the last line, and there's nothing else remaining on the line
            // then output no additional token.
            if ($this->offset < $lineLength) {
                $tokens[] = [
                    'scopes' => $this->scopeStack,
                    'text' => substr($line, $this->offset, $lineLength - $this->offset) . ((!$this->data->lastLine) ? "\n" : '')
                ];
            } elseif (!$this->data->lastLine) {
                $tokens[] = [
                    'scopes' => $this->scopeStack,
                    'text' => "\n"
                ];
            }

            assert($this->debugTokens($tokens));
            yield $lineNumber => $tokens;
        }
    }


    protected function resolveScopeName(string $scopeName, array $match): string {
        return preg_replace_callback(self::SCOPE_RESOLVE_REGEX, function($m) use($match) {
            $replacement = trim($match[(int)$m[1]][0] ?? $m[1]);
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

    protected function tokenizeLine(int $stopOffset): array {
        $tokens = [];
        $injected = false;

        while (true) {
            // Grab the current rule list from the cache if available to prevent having to
            // splice in references repeatedly.
            $cacheIndex = array_search(end($this->ruleStack)->patterns, $this->ruleCacheIndexes);
            if ($cacheIndex !== false) {
                $currentRules = $this->ruleCacheValues[$cacheIndex];
            } else {
                $currentRules = end($this->ruleStack)->patterns;

                if (!$this->activeInjection && $this->grammar->injections !== null) {
                    foreach ($this->grammar->injections as $selector => $injection) {
                        $selector = ScopeParser::parseSelector($selector);
                        if ($selector->matches($this->scopeStack)) {
                            $prefix = $selector->getPrefix($this->scopeStack);
                            if ($prefix === Filter::PREFIX_LEFT || $prefix === Filter::PREFIX_BOTH) {
                                $currentRules = [ ...$injection->patterns, ...$currentRules ];
                                if ($prefix === Filter::PREFIX_LEFT) {
                                    break;
                                }
                            }
                            if ($prefix === null || $prefix === Filter::PREFIX_RIGHT || $prefix === Filter::PREFIX_BOTH) {
                                $currentRules = [ ...$currentRules, ...$injection->patterns ];
                            }

                            $injected = true;
                            break;
                        }
                    }
                }
            }

            $currentRulesCount = count($currentRules);
            $closestMatch = null;

            // Iterate through the rules to find matches for the line at the current offset.
            for ($i = 0; $i < $currentRulesCount; $i++) {
                while (true) {
                    $rule = $currentRules[$i];

                    // Grammar references can return false if the grammar does not exist, so
                    // continue on if the current rule is false.
                    if ($rule === false) {
                        continue 2;
                    }

                    // If the rule is a Pattern
                    if ($rule instanceof Pattern) {
                        if (preg_match($rule->match, $this->line . ((!$this->data->lastLine) ? "\n" : ''), $match, PREG_OFFSET_CAPTURE, $this->offset) === 1) {
                            // Throw out pattern regexes with anchors that shouldn't match the current line.
                            // This is necessary because the tokenizer is fed data line by line and
                            // therefore anchors that match the beginning of the document and the end won't
                            // do anything.
                            if (preg_match(
                                    self::ANCHOR_CHECK_REGEX, $rule->match, $validRegexMatch) === 1 && (
                                        // \A anchors match the beginning of the whole string, not just this line
                                        ($validRegexMatch[1] === 'A' && !$this->data->firstLine) ||
                                        // \z anchors match the end of the whole string, not just this line
                                        ($validRegexMatch[1] === 'z' && !$this->data->lastLine) ||
                                        // \Z anchors match the end of the whole string or before the final newline if
                                        // there's a trailing newline in the string
                                        ($validRegexMatch[1] === 'Z' && !$this->data->lastLineBeforeFinalNewLine)
                                    )
                                ) {
                                continue 2;
                            }

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
                    elseif ($rule instanceof Reference) {
                        if (!$rule instanceof BaseReference) {
                            $obj = $rule->get();
                            if ($obj instanceof Grammar || ($rule instanceof RepositoryReference && $obj->match === null)) {
                                $obj = $obj->patterns;
                            }
                        } else {
                            $obj = $this->grammar->patterns;
                        }

                        array_splice($currentRules, $i, 1, ($obj instanceof Pattern) ? [ $obj ] : $obj);
                        $currentRulesCount = count($currentRules);

                        // When the current rule list changes write it to the cache.
                        if ($cacheIndex === false) {
                            $this->ruleCacheIndexes[] = end($this->ruleStack)->patterns;
                            $cacheIndex = count($this->ruleCacheIndexes) - 1;
                        }

                        if ($injected) {
                            // Injections need to be re-evaluated against the scope stack every time they're
                            // injected so don't cache them.
                            $temp = $currentRules;
                            foreach ($temp as $k => $r) {
                                if ($r instanceof Pattern && $r->injection) {
                                    unset($temp[$k]);
                                }
                            }
                            $this->ruleCacheValues[$cacheIndex] = array_values($temp);
                        } else {
                            $this->ruleCacheValues[$cacheIndex] = $currentRules;
                        }

                        continue;
                    }

                    break;
                }
            }

            assert($this->debugClosestMatch($closestMatch));

            // If there were a match above...
            if ($closestMatch !== null) {
                $match = $closestMatch['match'];
                $pattern = $closestMatch['pattern'];

                // If the subpattern begins after the offset then create a token from the bits
                // of the line in-between the last token and the one(s) about to be created.
                if ($match[0][1] > $this->offset) {
                    $tokens[] = [
                        'scopes' => $this->scopeStack,
                        'text' => substr($this->line, $this->offset, $match[0][1] - $this->offset)
                    ];
                    $this->offset = $match[0][1];
                }

                // If the pattern is an end pattern and its corresponding begin pattern has a
                // content name remove that from the scope stack before continuing.
                if ($pattern->endPattern) {
                    $previousRule = end($this->ruleStack);
                    if ($previousRule->beginPattern && $previousRule->contentName !== null) {
                        array_pop($this->scopeStack);
                    }
                }

                // Add the name to the scope stack if present.
                if ($pattern->name !== null) {
                    $this->scopeStack[] = $this->resolveScopeName($pattern->name, $match);
                }

                // If a rule has captures iterate through each of the matched subpatterns and
                // create tokens from the captures.
                if ($pattern->captures !== null) {
                    foreach ($match as $k => $m) {
                        // If either the capture match is empty, there's no pattern capture for this
                        // match, or the match being processed is the first one and there are no
                        // captures for it then continue onto the next one.
                        if ($m[0] === '' || $m[1] < 0 || !isset($pattern->captures[$k]) || ($k === 0 && !isset($pattern->captures[0]))) {
                            continue;
                        }

                        // If the capture begins after the offset then create a token from the bits of
                        // the line in-between the last token and the one(s) about to be created.
                        if ($k > 0 && $m[1] > $this->offset) {
                            $tokens[] = [
                                'scopes' => $this->scopeStack,
                                'text' => substr($this->line, $this->offset, $m[1] - $this->offset)
                            ];

                            $this->offset = $m[1];
                        }

                        // If the capture has a name add it to the scope stack.
                        if ($pattern->captures[$k]->name !== null) {
                            $this->scopeStack[] = $this->resolveScopeName($pattern->captures[$k]->name, $match);
                        }

                        // If the capture has patterns of its own add the capture to the rule stack,
                        // process the patterns, and then pop the capture off the stack.
                        if ($pattern->captures[$k]->patterns !== null) {
                            if ($m[1] < $this->offset) {
                                die("MOTHERFUCKER!\n");
                            }

                            $this->ruleStack[] = $pattern->captures[$k];
                            // Don't do injections on capture lists.
                            $activeInjection = $this->activeInjection;
                            $this->activeInjection = true;
                            // Only tokenize the part of the line that's contains the match.
                            $captureEndOffset = $m[1] + strlen($m[0]);
                            $tokens = [ ...$tokens, ...$this->tokenizeLine($captureEndOffset) ];

                            // If the offset is before the end of the capture then create a token from the
                            // bits of the capture from the offset until the end of the capture.
                            if ($captureEndOffset > $this->offset) {
                                $tokens[] = [
                                    'scopes' => $this->scopeStack,
                                    'text' => substr($this->line, $this->offset, $captureEndOffset - $this->offset)
                                ];
                                $this->offset = $captureEndOffset;
                            }

                            array_pop($this->ruleStack);
                            // Return to the original active injection state.
                            $this->activeInjection = $activeInjection;
                            $this->offset = $m[1] + strlen($m[0]);
                        }
                        // Otherwise, create a token for the capture.
                        else {
                            // If the capture's offset is before the current offset then the new token needs
                            // to be spliced within previously emitted ones.
                            if ($m[1] < $this->offset) {
                                $curOffset = $this->offset;
                                // Go backwards through the tokens, find the token the current capture is
                                // within, and splice new tokens into the token array
                                for ($tokensLength = count($tokens), $i = $tokensLength - 1; $i >= 0; $i--) {
                                    $cur = $tokens[$i];
                                    $curOffset -= strlen($cur['text']);
                                    if ($m[1] >= $curOffset) {
                                        // If the length of the new capture would put part of it outside the previous
                                        // token then toss the token.
                                        if ($m[1] + strlen($m[0]) > $curOffset + strlen($cur['text'])) {
                                            // TODO: trigger a warning or something here maybe?
                                            break;
                                        }

                                        $t = [];

                                        // Add in token for anything before the new capture token within the token being
                                        // spliced
                                        $preMatchText = substr($cur['text'], 0, $m[1] - $curOffset);
                                        if ($preMatchText !== '') {
                                            $t[] = [
                                                'scopes' => $cur['scopes'],
                                                'text' => $preMatchText
                                            ];
                                        }

                                        // The new capture's scope needs to be added to the prior token's scope stack to
                                        // make the stack for the new one.
                                        $scopeStack = $cur['scopes'];
                                        $scopeStack[] = $pattern->captures[$k]->name;
                                        $t[] = [
                                            'scopes' => $scopeStack,
                                            'text' => $m[0]
                                        ];

                                        // Add in token for anything after the new capture token within the token being
                                        // spliced
                                        $postMatchText = substr($cur['text'], $m[1] - $curOffset + strlen($m[0]));
                                        if ($postMatchText !== '') {
                                            $t[] = [
                                                'scopes' => $cur['scopes'],
                                                'text' => $postMatchText
                                            ];
                                        }

                                        array_splice($tokens, $i, 1, $t);

                                        // Find the nearest index to the match that doesn't have an invalid offset value
                                        // (meaning that particular capture matched nothing) and set the offset to the
                                        // end of that match.
                                        $j = count($match) - 2;
                                        while ($match[$j][1] === -1 || $match[$j][1] === null) {
                                            $j--;
                                        }

                                        $this->offset = $match[$j][1] + strlen($match[$j][0]);
                                        break;
                                    }
                                }
                            } else {
                                $tokens[] = [
                                    'scopes' => $this->scopeStack,
                                    'text' => $m[0]
                                ];

                                $this->offset = $m[1] + strlen($m[0]);
                            }
                        }

                        // Pop the capture's name off the scope stack.
                        if ($pattern->captures[$k]->name !== null) {
                            array_pop($this->scopeStack);
                        }
                    }
                }
                // Otherwise, if the rule doesn't have captures then a token is created from the
                // entire match, but only if the matched text isn't empty.
                elseif ($match[0][0] !== '') {
                    $tokens[] = [
                        'scopes' => $this->scopeStack,
                        'text' => $match[0][0]
                    ];

                    $this->offset = $match[0][1] + strlen($match[0][0]);
                }

                // If the pattern is a begin pattern and has a content name then add that to the
                // scope stack before processing the children.
                if ($pattern->beginPattern && $pattern->contentName !== null) {
                    $this->scopeStack[] = $this->resolveScopeName($pattern->contentName, $match);
                }

                $this->ruleStack[] = $pattern;
                $this->activeInjection = ($pattern->injection);

                // If the rule has patterns process tokens from its subpatterns.
                if ($pattern->patterns !== null && $this->offset < $stopOffset) {
                    // If the pattern has just a regular match (meaning neither a begin nor an end
                    // pattern) but has subpatterns then only tokenize the part of the line that's
                    // within the match. Otherwise, tokenize up to the stop offset. Because of
                    // recursion, the stop offset could be set by this step before or within the
                    // capture tokenization process.
                    $tokens = [ ...$tokens, ...$this->tokenizeLine((!$pattern->beginPattern && !$pattern->endPattern) ? strlen($match[0][0]) : $stopOffset) ];
                }

                // If the offset is before the end of the match then create a token from the
                // bits of the match from the offset until the end of the match.
                $endOffset = $match[0][1] + strlen($match[0][0]);
                if ($endOffset > $this->offset) {
                    $tokens[] = [
                        'scopes' => $this->scopeStack,
                        'text' => substr($this->line, $this->offset, $endOffset - $this->offset)
                    ];
                    $this->offset = $endOffset;
                }

                if (!$pattern->beginPattern) {
                    if ($pattern->endPattern) {
                        // Pop everything off both stacks until a begin pattern is reached.
                        while (!end($this->ruleStack)->beginPattern) {
                            $popped = array_pop($this->ruleStack);

                            if ($popped->name !== null) {
                                array_pop($this->scopeStack);
                            }

                            // If what was just popped is the active injection then remove it, too.
                            if ($popped->injection) {
                                $this->activeInjection = false;
                            }
                        }
                    }

                    $popped = array_pop($this->ruleStack);
                    // Pop the rule's name from the stack.
                    if ($popped->name !== null) {
                        array_pop($this->scopeStack);
                    }

                    // If what was just popped is the active injection then remove it, too.
                    if ($popped->injection) {
                        $this->activeInjection = false;
                    }
                }

                // If the offset isn't at the end of the line then look for more matches.
                if ($this->offset < $stopOffset) {
                    continue;
                }
            }

            break;
        }

        return $tokens;
    }


    private function debugClosestMatch(?array $closestMatch): bool {
        if (self::$debug) {
            $message = <<<DEBUG
            Offset: %s
            Regex: %s
            Scope: %s
            HasCaptures: %s
            BeginPattern: %s
            EndPattern: %s
            Match: %s
            DEBUG;

            $message = sprintf($message,
                $this->offset,
                $closestMatch['pattern']->match ?? 'NULL',
                $closestMatch['pattern']->name ?? 'NULL',
                ($closestMatch !== null && $closestMatch['pattern']->captures !== null) ? 'yes' : 'no',
                var_export($closestMatch['pattern']->beginPattern ?? null, true),
                var_export($closestMatch['pattern']->endPattern ?? null, true),
                var_export($closestMatch['match'] ?? null, true)
            );

            echo $this->debug_indentLines($message) . "\n\n";
        }

        return true;
    }

    private function debug_indentLines(string $message): string {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        array_shift($backtrace);

        $count = -1;
        foreach ($backtrace as $b) {
            if ($b['function'] === 'tokenizeLine') {
                $count++;
            }
        }

        return ($count > 0) ? preg_replace('/^/m', str_repeat('|', $count) . ' ', $message) : $message;
    }

    private function debugLine(): bool {
        if (self::$debug) {
            $message = <<<DEBUG
            %s
            Line: %s


            DEBUG;

            printf(
                $message,
                str_pad("{$this->lineNumber} ", 80, '-'),
                preg_replace('/\\\\{2}/', '\\', var_export($this->line, true))
            );
        }

        return true;
    }

    public function debugTokens(array $tokens): bool {
        if (self::$debug) {
            echo 'Tokens: ' . preg_replace('/\\\\{2}/', '\\', var_export($tokens, true)) . "\n\n";
        }

        return true;
    }
}