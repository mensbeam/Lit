<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    protected Data $data;
    protected array $lastExceptionData = [];

    protected static Parser $instance;

    protected function __construct(string $selector) {
        $this->data = new Data($selector);
    }

    public static function parse(string $selector): Matcher|false {
        self::$instance = new self($selector);

        $result = false;
        $s1 = self::parseSpace();
        if ($s1 !== false) {
            $s2 = self::parseSelector();
            if ($s2 !== false) {
                $s3 = self::parseSpace();
                if ($s3 !== false) {
                    $result = $s2;
                }
            }
        }

        if (self::$instance->lastExceptionData !== []) {
            throw new Exception(self::$instance->lastExceptionData['expected'], self::$instance->lastExceptionData['found']);
        }

        return $result;
    }

    protected static function fail(array|string $expected) {
        self::$instance->lastExceptionData = [
            'expected' => $expected,
            'found' => self::$instance->data->peek()
        ];
    }

    protected static function parseComposite(): Matcher|false {
        $result = false;

        $s1 = self::parseExpression();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf('|&-');
                if (!in_array($s3, [ '|', '&', '-' ])) {
                    self::fail([ '|', '&', '-' ]);
                } else {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $s5 = self::parseComposite();
                        if ($s5 !== false) {
                            $result = new Matcher\CompositeMatcher($s1, $s3, $s5);
                        }
                    }
                }
            }
        }

        if ($result === false) {
            $result = self::parseExpression();
        }

        return $result;
    }

    protected static function parseExpression(): Matcher|false {
        $result = false;
        $s1 = self::$instance->data->consumeIf('-');
        if ($s1 !== '-') {
            self::fail('-');
        }

        $s2 = self::parseSpace();
        if ($s2 !== false) {
            $s3 = self::parseGroup();
            if ($s3 !== false) {
                $s4 = self::parseSpace();
                if ($s4 !== false) {
                    $result = new Matcher\NegateMatcher($s3);
                }
            }
        }

        if ($result === false) {
            $s1 = self::$instance->data->consumeIf('-', 1);
            if ($s1 !== '-') {
                self::fail('-');
            } else {
                $s2 = self::parseSpace();
                if ($s2 !== false) {
                    $s3 = self::parsePath();
                    if ($s3 !== false) {
                        $s4 = self::parseSpace();
                        if ($s4 !== false) {
                            $result = new Matcher\NegateMatcher($s3);
                        }
                    }
                }
            }
        }

        if ($result === false) {
            $result = self::parseGroup();
            if ($result === false) {
                $result = self::parsePath();
            }
        }

        return $result;
    }

    protected static function parseGroup(): Matcher|false {
        $result = false;

        $s2 = self::$instance->data->consumeIf('BLR');
        if (!in_array($s2, [ 'B', 'L', 'R' ])) {
            self::fail([ 'B', 'L', 'R' ]);
        } else {
            $s3 = self::$instance->data->consumeIf(':');
            if ($s3 !== ':') {
                self::fail(':');
            } else {
                $prefix = "$s2$s3";

                $s2 = self::$instance->data->consumeIf('(');
                if ($s2 !== '(') {
                    self::fail('(');
                } else {
                    $s3 = self::parseSpace();
                    if ($s3 !== false) {
                        $s4 = self::parseSelector();
                        if ($s4 !== false) {
                            $s5 = self::parseSpace();
                            if ($s5 !== false) {
                                $s6 = self::$instance->data->consumeIf(')');
                                if ($s6 !== ')') {
                                    self::fail(')');
                                } else {
                                    $result = new GroupMatcher($prefix, $s4);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    protected static function parsePath(): Matcher|false {
        $result = false;

        $s2 = self::$instance->data->consumeIf('BLR');
        if (!in_array($s2, [ 'B', 'L', 'R' ])) {
            self::fail([ 'B', 'L', 'R' ]);
        } else {
            $s3 = self::$instance->data->consumeIf(':');
            if ($s3 !== ':') {
                self::fail(':');
            } else {
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
                        $result = new Matcher\PathMatcher($prefix, $s2, $s3);
                    }
                }
            }
        }

        return $result;
    }

    protected static function parseSpace(): string|false {
        return self::$instance->data->consumeIf(" \t");
    }

    protected static function parseSegment(): string|false {
        return false;
    }

    protected static function parseSelector(): Matcher|false {
        $result = false;
        $s1 = self::parseComposite();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf(',');
                if ($s3 !== ',') {
                    self::fail(',');
                } else {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $s5 = self::parseSelector();
                        if ($s5 !== false) {
                            $result = new Matcher\OrMatcher($s1, $s5);
                        }
                    }
                }
            }
        }

        if ($result === false) {
            $result = self::parseComposite();
        }

        return $result;
    }

    protected static function parseScope(): string|false {
        $result = false;

        $s1 = self::parseSegment();
        if ($s1 !== false) {
            $s2 = '';
            $s3 = '';

            while ($s3 !== false) {
                $s2 .= $s3;
                $s3 = false;

                $s4 = self::$instance->data->consumeIf('.');
                if ($s4 !== '.') {
                    self::fail('.');
                } else {
                    $s5 = self::parseSegment();
                    if ($s5 !== false) {
                        $s3 = "$s4$s5";
                    }
                }
            }

            if (strlen($s2) > 0) {
                $result = new Matcher\ScopeMatcher($s1, $s2);
            }
        }

        return $result;
    }
}
