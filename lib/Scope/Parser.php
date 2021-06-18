<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    protected string $token;
    protected Tokenizer $tokenizer;

    protected static Parser $instance;


    protected function __construct(string $selector) {
        $this->tokenizer = new Tokenizer($selector);
    }


    public static function parse(string $selector): array {
        self::$instance = new self($selector);

        $result = [];
        while (self::$instance->token = self::$instance->tokenizer->next()) {
            $priority = 0;
            if (strlen(self::$instance->token) === 2 && self::$instance->token[1] === ':') {
                switch (self::$instance->token[0]) {
                    case 'R': $priority = 1;
                    break;
                    case 'L': $priority = -1;
                    break;
                    default: die('OOK!');
                }

                self::$instance->token = self::$instance->tokenizer->next();
                if (self::$instance->token === false) {
                    break;
                }
            }

            $matcher = self::parseConjunction();
            if ($matcher === false) {
                $matcher = self::parseOperand();
            }

            $result[] = [
                'matcher' => $matcher,
                'priority' => $priority
            ];

            if (self::$instance->token !== ',') {
        		break;
        	}
        }

        return $result;
    }


    protected static function parseConjunction(): AndMatcher|false {
        $matchers = [];
        while ($matcher = self::parseOperand()) {
            $matchers[] = $matcher;
        }

        return (count($matchers) > 1) ? new AndMatcher($matchers[0], $matchers[1]) : false;
    }

    protected static function parseInnerExpression(): Matcher|false {
        $matchers = [];
        while ($matcher = self::parseConjunction()) {
            $matchers[] = $matcher;
            if (self::$instance->token === '|' || self::$instance->token === ',') {
                do {
                    self::$instance->token = self::$instance->tokenizer->next();
                } while (self::$instance->token === '|' || self::$instance->token === ',');
            } else {
                break;
            }
        }

        return (count($matchers) > 1) ? new OrMatcher($matchers[0], $matchers[1]) : false;
    }

    protected static function parseOperand(): Matcher|false {
        if (self::$instance->token === '-') {
            self::$instance->token = self::$instance->tokenizer->next();

            $matcher = self::parseOperand();
            if ($matcher === false) {
                die('OH SHIT');
            }

            return new NegateMatcher($matcher);
        }

        if (self::$instance->token === '(') {
            self::$instance->token = self::$instance->tokenizer->next();
            $expressionInParents = self::parseInnerExpression();
            if (self::$instance->token === ')') {
                self::$instance->token = self::$instance->tokenizer->next();
            }
            return $expressionInParents;
        }

        if (self::$instance->tokenizer->tokenIsIdentifier()) {
            $identifiers = [];
            do {
                $identifiers[] = self::$instance->token;
                self::$instance->token = self::$instance->tokenizer->next();
            } while (self::$instance->tokenizer->tokenIsIdentifier());

            return new ScopeMatcher(...$identifiers);
        }

        return false;
    }
}
