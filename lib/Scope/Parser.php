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


    protected static function parseComposite(?Selector $parent = null): Composite {
        assert((fn() => self::debug())());

        $result = new Composite($parent);
        $expressions = [ self::parseExpression($result) ];

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

            $expressions[] = self::parseExpression($result, $operator);
            $peek = self::$instance->data->peek();
        }

        $result->add(...$expressions);

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parseExpression(Composite $parent, int $operator = Expression::OPERATOR_NONE): Expression {
        assert((fn() => self::debug())());

        $result = new Expression($parent, $operator);

        $peek = self::$instance->data->peek();
        if (in_array($peek[0], [ 'B', 'L', 'R' ])) {
            $result->child = self::parseFilter($result, self::$instance->data->consume()[0]);
        } elseif ($peek === '(') {
            $result->child = self::parseGroup($result);
        } else {
            $result->child = self::parsePath($result);
        }

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parseFilter(Expression $parent, string $side): Filter {
        assert((fn() => self::debug())());

        $result = new Filter($parent, $side);
        $peek = self::$instance->data->peek();
        if ($peek === '(') {
            $result->child = self::parseGroup($result);
        } else {
            $result->child = self::parsePath($result);
        }

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parseGroup(Expression|Filter $parent): Group {
        assert((fn() => self::debug())());

        $result = new Group($parent);

        $token = self::$instance->data->consume();
        if ($token !== '(') {
            self::throw('"("', $token);
        }

        $result->child = self::parseSelector($result);

        $token = self::$instance->data->consume();
        if ($token !== ')') {
            self::throw('")"', $token);
        }

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parsePath(Expression $parent): Path {
        assert((fn() => self::debug())());

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
        $peek = self::$instance->data->peek();
        while ($peek !== false && $peek !== '-' && ($peek === '>' || strspn($peek, self::SCOPE_MASK) === strlen($peek))) {
            $anchorToPrevious = false;
            if ($peek === '>') {
                self::$instance->data->consume();
                $peek = self::$instance->data->peek();
                $anchorToPrevious = true;
            }
            $scopes[] = self::parseScope($result, $anchorToPrevious);
            $peek = self::$instance->data->peek();
        }

        $result->add(...$scopes);

        $anchorEnd = false;
        if ($peek === '$') {
            $anchorEnd = true;
            self::$instance->data->consume();
        }

        if ($anchorStart && $anchorEnd) {
            $result->anchor = Path::ANCHOR_BOTH;
        } elseif ($anchorStart) {
            $result->anchor = Path::ANCHOR_START;
        } elseif ($anchorEnd) {
            $result->anchor = Path::ANCHOR_END;
        } else {
            $result->anchor = Path::ANCHOR_NONE;
        }

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parseSelector(?Group $parent = null): Selector {
        assert((fn() => self::debug())());

        $result = new Selector($parent);

        $composites = [ self::parseComposite($result) ];
        $peek = self::$instance->data->peek();
        while ($peek === ',') {
            self::$instance->data->consume();
            $composites[] = self::parseComposite($result);
            $peek = self::$instance->data->peek();
        }

        $result->add(...$composites);

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function parseScope(Path $parent): Scope {
        assert((fn() => self::debug())());

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
            if ($peek !== false && strspn($peek, self::SCOPE_MASK) !== strlen($peek)) {
                self::throw([ 'A-Z', 'a-z', '0-9', '-', '+', '_', '*' ], $peek);
            }

            $atoms[] = self::$instance->data->consume();
            $first = false;
        } while (self::$instance->data->peek() === '.');

        $result->add(...$atoms);

        assert((fn() => self::debugResult($result))());

        return $result;
    }

    protected static function debug(): bool {
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

        return true;
    }

    protected static function debugResult($result): bool {
        printf("%s Result: %s\n",
            debug_backtrace()[2]['function'],
            // Removes bullshit from var_exported classes for easier reading
            str_replace([ '::__set_state(array', __NAMESPACE__.'\\', '))' ], [ '', '', ')' ], var_export($result, true)));
        return true;
    }

    protected static function throw(array|string $expected, string|bool $found) {
        throw new Exception($expected, $found, self::$instance->data->offset());
    }
}
