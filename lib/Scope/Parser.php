<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    protected Data $data;
    protected static Parser $instance;

    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    public static function parse(string $selector): Matcher|bool {
        self::$instance = new self($selector);

        $output = false;
        $s1 = self::parseSpace();
        if ($s1 !== false) {
            $s2 = self::parseSelector();
            if ($s2 !== false) {
                $s3 = self::parseSpace();
                if ($s3 !== false) {
                    $output = $s2;
                }
            }
        }

        return $output;
    }

    protected static function parseComposite(): Matcher|bool {
        $output = false;

        $s1 = self::parseExpression();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf('|&-');
                if (!in_array($s3, [ '|', '&', '-' ])) {
                    throw new Exception([ '|', '&', '-' ], self::$instance->data->peek());
                }

                $s4 = self::parseSpace();
                if ($s4 !== false) {
                    $s5 = self::parseComposite();
                    if ($s5 !== false) {
                        $output = new Matcher\CompositeMatcher($s1, $s3, $s5);
                    }
                }
            }
        }

        if ($output === false) {
            $output = self::parseExpression();
        }

        return $output;
    }

    protected static function parseExpression(): Matcher|bool {
        $output = false;
        $s1 = self::$instance->data->consumeIf('-');
        if ($s1 !== '-') {
            throw new Exception('-', self::$instance->data->peek());
        }

        $s2 = self::parseSpace();
        if ($s2 !== false) {
            $s3 = self::parseGroup();
            if ($s3 !== false) {
                $s4 = self::parseSpace();
                if ($s4 !== false) {
                    $output = new Matcher\NegateMatcher($s3);
                }
            }
        }

        if ($output === false) {
            $s1 = self::$instance->data->consumeIf('-', 1);
            if ($s1 !== '-') {
                throw new Exception('-', self::$instance->data->peek());
            }

            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::parsePath();
                if ($s3 !== false) {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $output = new Matcher\NegateMatcher($s3);
                    }
                }
            }
        }

        if ($output === false) {
            $output = self::parseGroup();
            if ($output === false) {
                $output = self::parsePath();
            }
        }

        return $output;
    }

    protected static function parseGroup(): Matcher|bool {
        $output = false;

        $s2 = self::$instance->data->consumeIf('BLR');
        if (!in_array($s2, [ 'B', 'L', 'R' ])) {
            throw new Exception([ 'B', 'L', 'R' ], self::$instance->data->peek());
        }

        $s3 = self::$instance->data->consumeIf(':');
        if ($s3 !== ':') {
            throw new Exception(':', self::$instance->data->peek());
        }

        $prefix = "$s2$s3";

        $s2 = self::$instance->data->consumeIf('(');
        if ($s2 !== '(') {
            throw new Exception('(', self::$instance->data->peek());
        }

        $s3 = self::parseSpace();
        if ($s3 !== false) {
            $s4 = self::parseSelector();
            if ($s4 !== false) {
                $s5 = self::parseSpace();
                if ($s5 !== false) {
                    $s6 = self::$instance->data->consumeIf(')');
                    if ($s6 !== ')') {
                        throw new Exception(')', self::$instance->data->peek());
                    }

                    $output = new GroupMatcher($prefix, $s4);
                }
            }
        }

        return $output;
    }

    protected static function parsePath(): Matcher|bool {
        $output = false;

        $s2 = self::$instance->data->consumeIf('BLR');
        if (!in_array($s2, [ 'B', 'L', 'R' ])) {
            throw new Exception([ 'B', 'L', 'R' ], self::$instance->data->peek());
        }

        $s3 = self::$instance->data->consumeIf(':');
        if ($s3 !== ':') {
            throw new Exception(':', self::$instance->data->peek());
        }

        $prefix = "$s2$s3";

        $s2 = self::parseScope();
        if ($s2 !== false) {
            $s3 = '';
            $s4 = '';

            while ($s4 !== false) {
                $s3 .= $s4;
                $s4 = false;

                $s5 = self::parseSpace();
                if ($s5 !== false) {
                    $s6 = self::parseScope();
                    if ($s6 !== false) {
                        $s4 = "$s5$s6";
                    }
                }
            }

            if (strlen($s3) > 0) {
                $output = new Matcher\PathMatcher($prefix, $s2, $s3);
            }
        }

        return $output;
    }

    protected static function parseSpace(): string|bool {
        return self::$instance->data->consumeIf(" \t");
    }

    protected static function parseSegment(): string|bool {
        return false;
    }

    protected static function parseSelector(): Matcher|bool {
        $output = false;
        $s1 = self::parseComposite();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf(',');
                if ($s3 !== ',') {
                    throw new Exception(',', self::$instance->data->peek());
                }

                $s4 = self::parseSpace();
                if ($s4 !== false) {
                    $s5 = self::parseSelector();
                    if ($s5 !== false) {
                        $output = new Matcher\OrMatcher($s1, $s5);
                    }
                }
            }
        }

        if ($output === false) {
            $output = self::parseComposite();
        }

        return $output;
    }

    protected static function parseScope(): string|bool {
        $output = false;

        $s1 = self::parseSegment();
        if ($s1 !== false) {
            $s2 = '';
            $s3 = '';

            while ($s3 !== false) {
                $s2 .= $s3;
                $s3 = false;

                $s4 = self::$instance->data->consumeIf('.');
                if ($s4 !== '.') {
                    throw new Exception('.', self::$instance->data->peek());
                }

                $s5 = self::parseSegment();
                if ($s5 !== false) {
                    $s3 = "$s4$s5";
                }
            }

            if (strlen($s2) > 0) {
                $output = new Matcher\ScopeMatcher($s1, $s2);
            }
        }

        return $output;
    }
}
