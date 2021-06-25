<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Fukkus\Scope;

/** Parses scope strings into a matcher tree */
class Parser {
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
    protected const SCOPE_MASK = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+_.*';


    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    /**
     * Static method entry point for the class. Creates the instance and parses the
     * string.
     */
    public static function parse(string $selector): Matcher|false {
        self::$instance = new self($selector);
        return self::parseSelector();
    }


    protected static function parseComposite(): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $result = self::parseExpression();

        $peek = self::$instance->data->peek();
        while (in_array($peek, [ '|', '&', '-' ])) {
            $token = self::$instance->data->consume();
            $new = self::parseExpression();

            switch ($token) {
                case '|':
                    $result = ($result instanceof OrMatcher) ? $result->add($new) : new OrMatcher($result, $new);
                break;

                case '-':
                    $new = new NegateMatcher($new);
                case '&':
                    $result = ($result instanceof AndMatcher) ? $result->add($new) : new AndMatcher($result, $new);
                break;
            }

            $peek = self::$instance->data->peek();
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseExpression(): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $peek = self::$instance->data->peek();
        $prefix = null;
        if ($peek !== false && in_array($peek[0], [ 'B', 'L', 'R' ]) && $peek[1] === ':') {
            $prefix = $peek[0];
            self::$instance->data->consume();
            $peek = self::$instance->data->peek();
        }

        if ($peek === '(') {
            self::$instance->data->consume();
            $result = self::parseGroup($prefix);
        } elseif (!in_array($peek, [ '-', false ]) && strspn($peek, self::SCOPE_MASK) === strlen($peek)) {
            $result = self::parsePath($prefix);
        } else {
            if (!in_array($peek, [ '-', false ]) && )
            // TODO: Take the effort to make this more descriptive
            self::throw([ 'Group', 'Path', 'Scope' ], $peek);
        }

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseGroup(string|null $prefix = null): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $result = self::parseSelector();
        $token = self::$instance->data->consume();
        if ($token !== ')') {
            self::throw('")"', $token);
        }

        $result = ($prefix === null) ? $result : new GroupMatcher($prefix, $result);

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parsePath(string|null $prefix = null): PathMatcher|ScopeMatcher {
        if (self::$debug) {
            self::debug();
        }

        $result = [];
        $result[] = self::parseScope();

        $peek = self::$instance->data->peek();
        while (!in_array($peek, [ '-', false ]) && strspn($peek, self::SCOPE_MASK) === strlen($peek)) {
            $result[] = self::parseScope();
            $peek = self::$instance->data->peek();
        }

        $result = ($prefix !== null || count($result) > 1) ? new PathMatcher($prefix, ...$result) : $result[0];

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseSelector(): Matcher {
        if (self::$debug) {
            self::debug();
        }

        $result = [];
        $result[] = self::parseComposite();

        $peek = self::$instance->data->peek();
        while ($peek === ',') {
            self::$instance->data->consume();
            $result[] = self::parseComposite();
            $peek = self::$instance->data->peek();
        }

        $result = (count($result) > 1) ? new OrMatcher(...$result) : $result[0];

        if (self::$debug) {
            self::debugResult($result);
        }

        return $result;
    }

    protected static function parseScope(): ScopeMatcher {
        if (self::$debug) {
            self::debug();
        }

        $token = self::$instance->data->consume();
        if ($token === false || !preg_match('/^(?:[A-Za-z0-9-_]+|\*)(?:\.(?:[A-Za-z0-9-+_]+|\*))*$/S', $token)) {
            // TODO: Take the effort to make this more descriptive
            self::throw('valid scope syntax', $token);
        }

        $result = new ScopeMatcher(...explode('.', $token));

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
