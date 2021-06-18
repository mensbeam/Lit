<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    protected Data $data;
    protected string $token;

    protected static Parser $instance;

    protected const PREFIX_REGEX = '/^[LRB]:$/S';
    protected const SCOPE_REGEX = '/^[A-Za-z0-9-+_\.]+$/S';


    public static function parse(string $selector): Matcher|false {
        self::$instance = new self($selector);
        return self::parseSelector();
    }


    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    protected static function parseSelector(): Matcher {
        while (self::$instance->token = self::$instance->data->consume()) {
            if (preg_match(self::PREFIX_REGEX, self::$instance->token)) {
                $peek = self::$instance->data->peek();
                if ($peek === '(') {
                    $result = self::parseGroup();
                } elseif (preg_match(self::SCOPE_REGEX, self::$instance->token)) {
                    $result = self::parsePath();
                } else {
                    die('Group or path expected.');
                }
            } elseif (preg_match(self::SCOPE_REGEX, self::$instance->token)) {
                $result = self::parseScope();
            } elseif (self::$instance->token === '(') {
                continue;
            } else {
                die('Group, path, or scope expected.');
            }

            return $result;
        }
    }

    protected static function parseScope(): Matcher {
        if (!preg_match('/^(?:[A-Za-z0-9-_]+|\*)(?:\.(?:[A-Za-z0-9-+_]+|\*))*$/S', self::$instance->token)) {
            die('Invalid scope');
        }

        $segments = explode('.', $token);
        foreach ($segments as $index => $segment) {
            $segments[$index] = ($segment !== '*') ? new SegmentMatcher($segment) : new TrueMatcher();
        }

        return new ScopeMatcher(...$segments);
    }
}
