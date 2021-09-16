<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Scope;

/** Parses strings into a scope selector */
class Parser {
    // Flag for turning on debugging on the class. Assertions must be enabled for
    // it to work. This allows for parsing of scopes to be debugged separately from
    // tokenization of input text.
    public static bool $debug = false;

    // Used to cache parsed scopes and selectors
    protected static array $cache = [
        'selector' => [],
        'scope' => []
    ];

    // The tokenized scope string
    protected Data $data;
    // Used for incrementing the blocks of debug information; useful for creating
    // breakpoints when debugging.
    protected int $debugCount = 1;

    // Used for instancing data tokens in static methods.
    protected static Parser $instance;

    // strspn mask used to check whether a token could be a valid scope.
    protected const SCOPE_MASK = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+_*';


    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    /** Parses strings into Selectors */
    public static function parseSelector(string $string): Selector {
        if (isset(self::$cache['selector'][$string])) {
            return self::$cache['selector'][$string];
        }

        self::$instance = new self($string);
        $result = self::_parseSelector();

        // If not at the end of input throw an exception.
        $token = self::$instance->data->consume();
        if ($token !== false) {
            self::throw(false, $token);
        }

        self::$cache['selector'][$string] = $result;
        return $result;
    }

    /** Parses strings into Scopes */
    public static function parseScope(string $string): Scope {
        if (isset(self::$cache['scope'][$string])) {
            return self::$cache['scope'][$string];
        }

        self::$instance = new self($string);
        $result = self::_parseScope();

        // If not at the end of input throw an exception.
        $token = self::$instance->data->consume();
        if ($token !== false) {
            self::throw(false, $token);
        }

        self::$cache['scope'][$string] = $result;
        return $result;
    }


    protected static function parseComposite(): Composite {
        assert((fn() => self::debugStart())());

        $expressions = [ self::parseExpression() ];

        $peek = self::$instance->data->peek();
        while ($peek !== false && in_array($peek, [ '&', '|', '-' ])) {
            self::$instance->data->consume();
            switch ($peek) {
                case '&': $operator = Expression::OPERATOR_AND;
                break;
                case '|': $operator = Expression::OPERATOR_OR;
                break;
                case '-': $operator = Expression::OPERATOR_NOT;
                break;
            }

            $expressions[] = self::parseExpression($operator);
            $peek = self::$instance->data->peek();
        }

        $result = new Composite(...$expressions);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function parseExpression(int $operator = Expression::OPERATOR_NONE): Expression {
        assert((fn() => self::debugStart())());

        $peek = self::$instance->data->peek();
        $negate = false;
        if ($peek === '-') {
            self::$instance->data->consume();
            $peek = self::$instance->data->peek();
            $negate = true;
        }

        if (in_array($peek[0], [ 'B', 'L', 'R' ])) {
            $child = self::parseFilter(self::$instance->data->consume()[0]);
        } elseif ($peek === '(') {
            $child = self::parseGroup();
        } else {
            $child = self::parsePath();
        }

        $result = new Expression($child, $operator, $negate);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function parseFilter(string $prefix): Filter {
        assert((fn() => self::debugStart())());

        $peek = self::$instance->data->peek();
        if ($peek === '(') {
            $child = self::parseGroup();
        } else {
            $child = self::parsePath();
        }

        $result = new Filter($child, $prefix);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function parseGroup(): Group {
        assert((fn() => self::debugStart())());

        $token = self::$instance->data->consume();
        if ($token !== '(') {
            self::throw('"("', $token);
        }

        $child = self::_parseSelector();

        $token = self::$instance->data->consume();
        if ($token !== ')') {
            self::throw('")"', $token);
        }

        $result = new Group($child);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function parsePath(): Path {
        assert((fn() => self::debugStart())());

        $anchorStart = false;
        if (self::$instance->data->peek() === '^') {
            $anchorStart = true;
            self::$instance->data->consume();
        }

        $first = self::_parseScope();
        if ($first->anchorToPrevious) {
            self::throw('first scope', '>');
        }

        $scopes = [ $first ];
        $peek = self::$instance->data->peek();
        while ($peek !== false && $peek !== '-' && ($peek === '>' || strspn($peek, self::SCOPE_MASK) === strlen($peek))) {
            $anchorToPrevious = false;
            if ($peek === '>') {
                self::$instance->data->consume();
                $peek = self::$instance->data->peek();
                $anchorToPrevious = true;
            }
            $scopes[] = self::_parseScope(end($scopes), $anchorToPrevious);
            $peek = self::$instance->data->peek();
        }

        $anchorEnd = false;
        if ($peek === '$') {
            $anchorEnd = true;
            self::$instance->data->consume();
        }

        if ($anchorStart && $anchorEnd) {
            $anchor = Path::ANCHOR_BOTH;
        } elseif ($anchorStart) {
            $anchor = Path::ANCHOR_START;
        } elseif ($anchorEnd) {
            $anchor = Path::ANCHOR_END;
        } else {
            $anchor = Path::ANCHOR_NONE;
        }

        $result = new Path($anchor, ...$scopes);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function _parseSelector(): Selector {
        assert((fn() => self::debugStart())());

        $composites = [ self::parseComposite() ];
        $peek = self::$instance->data->peek();
        while ($peek === ',') {
            self::$instance->data->consume();
            $composites[] = self::parseComposite();
            $peek = self::$instance->data->peek();
        }

        $result = new Selector(...$composites);
        assert((fn() => self::debugResult($result))());
        return $result;
    }

    protected static function _parseScope(?Scope $parent = null, bool $anchorToPrevious = false): Scope {
        assert((fn() => self::debugStart())());

        $atoms = [];
        $first = true;
        do {
            if (!$first) {
                // Consume the period
                self::$instance->data->consume();
            }

            $peek = self::$instance->data->peek();
            if ($peek !== false && strspn($peek, self::SCOPE_MASK) !== strlen($peek)) {
                self::throw([ 'A-Z', 'a-z', '0-9', '-', '+', '_', '*' ], $peek);
            }

            $atoms[] = self::$instance->data->consume();
            $first = false;
        } while (self::$instance->data->peek() === '.');

        $result = new Scope($parent, $anchorToPrevious, ...$atoms);
        assert((fn() => self::debugResult($result))());
        return $result;
    }


    private static function debugStart(): bool {
        if (self::$debug) {
            $message = <<<DEBUG
            ------------------------------
            %s
            Method: %s
            Position: %s
            Token: %s

            DEBUG;

            $methodTree = '';
            $backtrace = debug_backtrace();
            // Shift two off because it's executed in an assert closure
            array_shift($backtrace);
            array_shift($backtrace);
            // And, pop this method off the backtrace
            array_pop($backtrace);
            foreach ($backtrace as $b) {
                $methodTree = "->{$b['function']}$methodTree";
            }

            printf($message,
                self::$instance->debugCount++,
                ltrim($methodTree, '->'),
                self::$instance->data->position + 1,
                var_export(self::$instance->data->peek(), true)
            );
        }

        return true;
    }

    private static function debugResult($result): bool {
        if (self::$debug) {
            printf("%s Result: %s\n",
                debug_backtrace()[2]['function'],
                // Removes bullshit from var_exported classes for easier reading
                str_replace([ '::__set_state(array', __NAMESPACE__.'\\', '))' ], [ '', '', ')' ], var_export($result, true))
            );
        }

        return true;
    }

    protected static function throw(array|string|bool $expected, string|bool $found) {
        throw new ParserException($expected, $found, self::$instance->data->offset());
    }
}
