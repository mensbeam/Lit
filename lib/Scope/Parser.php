<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    protected Data $data;

    protected static Parser $instance;
    protected static bool $debug = false;

    protected const SCOPE_REGEX = '/^[A-Za-z0-9-+_\.\*]+$/S';


    public static function parse(string $selector): Matcher|false {
        self::$instance = new self($selector);
        return self::parseSelector();
    }


    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    protected static function parseComposite(): Matcher {
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

        return $result;
    }

    protected static function parseExpression(): Matcher {
        $token = self::$instance->data->consume();
        $prefix = null;
        if (in_array($token[0], [ 'B', 'L', 'R' ]) && $token[1] === ':') {
            $prefix = $token[0];
        }

        $token = self::$instance->data->consume();
        if ($token === '(') {
            $result = self::parseGroup($prefix);
        } elseif (preg_match(self::SCOPE_REGEX, $token)) {
            $result = self::parsePath($prefix);
        } else {
            die('Group or path expected.');
        }

        return $result;
    }

    protected static function parseGroup(string|null $prefix = null): Matcher {
        $result = self::parseSelector();
        if (self::$instance->data->consume() !== ')') {
            die('Close parenthesis expected');
        }

        return ($prefix === null) ? $result : new GroupMatcher($prefix, $result);
    }

    protected static function parsePath(string|null $prefix = null): Matcher {
        $result = [];
        $result[] = self::parseScope();

        $peek = self::$instance->data->peek();
        while (preg_match(self::SCOPE_REGEX, $peek)) {
            self::$instance->data->consume();
            $result[] = self::parseScope();
            $peek = self::$instance->data->peek();
        }

        return ($prefix !== null || count($result) > 1) ? new PathMatcher($prefix, ...$result) : $result;
    }

    protected static function parseSelector(): Matcher {
        $result = [];
        $result[] = self::parseComposite();

        $peek = self::$instance->data->peek();
        while ($peek === ',') {
            self::$instance->data->consume();
            $result[] = self::parseComposite();
            $peek = self::$instance->data->peek();
        }

        return (count($result) > 1) ? new OrMatcher(...$result) : $result[0];
    }

    protected static function parseScope(): Matcher {
        $token = self::$instance->data->consume();
        if (!preg_match('/^(?:[A-Za-z0-9-_]+|\*)(?:\.(?:[A-Za-z0-9-+_]+|\*))*$/S', $token)) {
            die('Invalid scope');
        }

        $segments = explode('.', $token);
        foreach ($segments as $index => $segment) {
            $segments[$index] = ($segment !== '*') ? new SegmentMatcher($segment) : new TrueMatcher();
        }

        return new ScopeMatcher(...$segments);
    }
}
