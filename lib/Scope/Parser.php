<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    public static bool $debug = false;

    protected Data $data;
    protected int $debugCount = 1;

    protected static Parser $instance;
    protected const SCOPE_REGEX = '/^[A-Za-z0-9-+_\.\*]+$/S';


    public static function parse(string $selector): Matcher|false {
        self::$instance = new self($selector);
        return self::parseSelector();
    }


    protected function __construct(string $selector) {
        $this->data = new Data($selector);
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
                    if ($result instanceof OrMatcher) {
                        $result = $result->add($new);
                    }

                    $result = new OrMatcher($result, $new);
                break;

                case '-':
                    $new = new NegateMatcher($new);
                case '&':
                    if ($result instanceof AndMatcher) {
                        $result = $result->add($new);
                    }

                    $result = new AndMatcher($result, $new);
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
        if (in_array($peek[0], [ 'B', 'L', 'R' ]) && $peek[1] === ':') {
            $prefix = $peek[0];
            self::$instance->data->consume();
        }

        $peek = self::$instance->data->peek();
        if ($peek === '(') {
            self::$instance->data->consume();
            $result = self::parseGroup($prefix);
        } elseif (preg_match(self::SCOPE_REGEX, $peek)) {
            $result = self::parsePath($prefix);
        } elseif ($peek !== false) {
            die('Group or path expected.');
        } else {
            die('Unexpected eod');
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
            die(($token !== false) ? 'Close parenthesis expected' : 'Unexpected eod');
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
        die(var_export($result));

        $peek = self::$instance->data->peek();
        while (!in_array($peek, [ '-', false ]) && preg_match(self::SCOPE_REGEX, $peek)) {
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
        if ($token === false) {
            die('Unexpected eod');
        } elseif (!preg_match('/^(?:[A-Za-z0-9-_]+|\*)(?:\.(?:[A-Za-z0-9-+_]+|\*))*$/S', $token)) {
            die('Invalid scope');
        }

        $segments = explode('.', $token);
        foreach ($segments as $index => $segment) {
            $segments[$index] = ($segment !== '*') ? new SegmentMatcher($segment) : new TrueMatcher();
        }

        $result = new ScopeMatcher(...$segments);

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
            str_replace([ '::__set_state(array', __NAMESPACE__ ], '', var_export($result, true)));
    }
}
