<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Scope;

/** Parses strings into a scope selector */
class Parser {
    // Used to cache parsed selectors
    protected static array $cache = [];

    // When true prints out detailed data about the construction of the matcher
    // tree.
    public static bool $debug = false;

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

    /** Static method entry point for the class. Parses the string. */
    public static function parse(string $string): Selector {
        if (isset(self::$cache[$string])) {
            return self::$cache[$string];
        }

        self::$instance = new self($string);
        $result = self::parseSelector();
        self::$cache[$string] = $result;
        return $result;
    }


    protected static function parseComposite(?Selector $parent = null): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $result = new Composite($parent);
        $expressions = [ self::parseExpression($result) ];

        while ($peek = self::$instance->data->peek() && in_array($peek, [ '&', '|', '-' ])) {
            $expressions[] = self::parseExpression($result, self::$instance->data->consume());
        }

        $result->add(...$expressions);

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseExpression(Composite $parent, ?string $operator = null): Expression {
        if (self::$debug) {
            self::debug();
        }

        $result = new Expression($parent, $operator);

        $peek = self::$instance->data->peek();
        if (in_array($peek[0], [ 'B', 'L', 'R' ])) {
            $result->child = self::parseFilter($result, self::$instance->data->consume()[0]);
        } elseif ($peek === '(') {
            $result->child = self::parseGroup($result);
        } else {
            $result->child = self::parsePath($result);
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseFilter(Expression $parent, string $side): Filter {
        if (self::$debug) {
            self::debug();
        }

        $result = new Filter($parent, $side);
        $peek = self::$instance->data->peek();
        if ($peek === '(') {
            $result->child = self::parseGroup($result);
        } else {
            $result->child = self::parsePath($result);
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseGroup(Expression $parent): Group {
        if (self::$debug) {
            self::debug();
        }

        $result = new Group($parent);

        $token = self::$instance->data->consume();
        if ($token !== '(') {
            self::throw('"("', $token);
        }

        if (!$group->child = self::parseSelector($result)) {
            return false;
        }

        $token = self::$instance->data->consume();
        if ($token !== ')') {
            self::throw('")"', $token);
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parsePath(Expression $parent): Path {
        if (self::$debug) {
            self::debug();
        }

        $result = new Path($parent);

        $anchorStart = false;
        if (self::$instance->data->peek() === '^') {
            $anchorStart = true;
            self::$instance->data->consume();
        }

        $first = self::parseScope($result);
        if ($first->anchorToPrevious) {
            self::throw('first scope', '>');
        }

        $scopes = [ $first ];
        while ($peek = self::$instance->data->peek() && strspn($peek, self::SCOPE_MASK) === strlen($peek)) {
            $scopes[] = self::parseScope($result);
        }

        $result->add(...$scopes);

        $anchorEnd = false;
        if (self::$instance->data->peek() === '$') {
            $anchorEnd = true;
            self::$instance->data->consume();
        }

        if ($anchorStart && $anchorEnd) {
            $result->anchor = Path::ANCHOR_BOTH;
        } elseif ($anchorStart) {
            $result->anchor = Path::ANCHOR_START;
        } else {
            $result->anchor = Path::ANCHOR_END;
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseSelector(?Group $parent = null): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $result = new Selector($parent);

        $composites = [ self::parseComposite($result) ];
        while ($peek = self::$instance->data->peek() && $peek === ',') {
            self::$instance->data->consume();
            $composites[] = self::parseComposite($result);
        }

        $result->add(...$composites);

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseScope(Path $parent): Scope {
        if (self::$debug) {
            self::debug();
        }

        $peek = self::$instance->data->peek();
        if ($peek === '>') {
            self::$instance->data->consume();
        }

        $result = new Scope($parent, ($peek === '>'));
        $atoms = [];
        $first = true;
        do {
            if (!$first) {
                // Consume the period
                self::$instance->data->consume();
            }

            $peek = self::$instance->data->peek();
            if (strspn($peek, self::SCOPE_MASK) !== strlen($peek)) {
                self::throw([ 'A-Z', 'a-z', '0-9', '-', '+', '_', '*' ], $peek);
            }

            $atoms[] = self::$instance->data->consume();
            $first = false;
        } while (self::$instance->data->peek() === '.');

        $result->add(...$atoms);

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function debug() {
        $message = <<<DEBUG
        ------------------------------
        %s
        Method: %s
        Position: %s
        Token: %s

        DEBUG;

        $methodTree = '';
        $backtrace = debug_backtrace();
        array_shift($backtrace);
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

    protected static function debugResult($result) {
        printf("%s Result: %s\n",
            debug_backtrace()[1]['function'],
            // Removes bullshit from printed classes for easier reading
            str_replace([ '::__set_state(array', __NAMESPACE__, '))' ], [ '', '', ')' ], var_export($result, true)));
    }

    protected static function throw(array|string $expected, string|bool $found) {
        throw new Exception($expected, $found, self::$instance->data->offset());
    }
}
