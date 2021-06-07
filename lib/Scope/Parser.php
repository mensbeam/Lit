<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Parser {
    public static $debug = false;

    protected Data $data;
    protected array $lastExceptionData = [];

    protected static $debugCount = 1;
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

        if (self::$debug === true) {
            echo "------------------------------\n";
        }

        if ($result === false && self::$instance->lastExceptionData !== []) {
            throw new Exception(self::$instance->lastExceptionData['expected'], self::$instance->lastExceptionData['found']);
        }

        return $result;
    }

    protected static function parseComposite(): Matcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;

        $s1 = self::parseExpression();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf('|&-');
                if ($s3 === '' || $s3 === false) {
                    $s3 = false;
                    self::fail('|&-');
                }

                if ($s3 !== false) {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $s5 = self::parseComposite();
                        if ($s5 !== false) {
                            $result = new CompositeMatcher($s1, $s3, $s5);
                        }
                    }
                }
            }
        }

        if ($result === false) {
            $result = self::parseExpression();
        }

        if (self::$debug === true) {
            echo "parseComposite Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseExpression(): Matcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;

        $s1 = self::$instance->data->consumeIf('-');
        if ($s1 === '' || $s1 === false) {
            $s1 = false;
            self::fail('-');
        }

        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::parseGroup();
                if ($s3 !== false) {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $result = new NegateMatcher($s3);
                    }
                }
            }
        }

        if ($result === false) {
            $s1 = self::$instance->data->consumeIf('-');
            if ($s1 === '' || $s1 === false) {
                $s1 = false;
                self::fail('-');
            }

            if ($s1 !== false) {
                $s2 = self::parseSpace();
                if ($s2 !== false) {
                    $s3 = self::parsePath();
                    if ($s3 !== false) {
                        $s4 = self::parseSpace();
                        if ($s4 !== false) {
                            $result = new NegateMatcher($s3);
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
        }

        if (self::$debug === true) {
            echo "parseExpression Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseGroup(): Matcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;
        $prefix = null;

        $s2 = self::$instance->data->consumeIf('LRB');
        if ($s2 === '' || $s2 === false) {
            $s2 = false;
            self::fail('LRB');
        }

        if ($s2 !== false) {
            $s3 = self::$instance->data->consumeIf(':');
            if ($s3 === '' || $s3 === false) {
                $s3 = false;
                self::fail(':');
            }

            if ($s3 !== false) {
                $prefix = "$s2$s3";
            }
        }

        if ($prefix !== null) {
            $s2 = self::$instance->data->consumeIf('(');
            if ($s2 === '' || $s2 === false) {
                $s2 = false;
                self::fail('(');
            }

            if ($s2 !== false) {
                $s3 = self::parse();
                if ($s3 !== false) {
                    $s4 = self::parseSelector();
                    if ($s4 !== false) {
                        $s5 = self::parseSpace();
                        if ($s5 !== false) {
                            $s6 = self::$instance->data->consumeIf(')');
                            if ($s6 === '' || $s6 === false) {
                                $s6 = false;
                                self::fail(')');
                            }

                            if ($s6 !== false) {
                                $result = new GroupMatcher($prefix, $s4);
                            }
                        }
                    }
                }
            }
        }

        if (self::$debug === true) {
            echo "parseGroup Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parsePath(): Matcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;
        $prefix = null;

        $s2 = self::$instance->data->consumeIf('LRB');
        if ($s2 === '' || $s2 === false) {
            $s2 = false;
            self::fail('LRB');
        }

        if ($s2 !== false) {
            $s3 = self::$instance->data->consumeIf(':');
            if ($s3 === '' || $s3 === false) {
                $s3 = false;
                self::fail(':');
            }

            if ($s3 !== false) {
                $prefix = "$s2$s3";
            }
        }

        $s2 = self::parseScope();
        if ($s2 !== false) {
            $s3 = [$s2];

            do {
                $s4 = false;
                $s5 = self::parseSpace();
                if ($s5 !== false) {
                    $s6 = self::parseScope();
                    if ($s6 !== false) {
                        $s3[] = $s6;
                    }
                }
            } while ($s4 !== false);

            $result = new PathMatcher($prefix, ...$s3);
        }

        if (self::$debug === true) {
            echo "parsePath Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseSegment(): SegmentMatcher|TrueMatcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;

        $s1 = self::parseSpace();
        if ($s1 !== false) {
            $s2 = self::$instance->data->consumeWhile('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+_');
            if ($s2 === '' || $s2 === false) {
                $s2 = false;
                self::fail('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+_');
            }

            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeWhile('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+_');
                if ($s3 === '' || $s2 === false) {
                    $s3 = false;
                    self::fail('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+_');
                } else {
                    $s2 .= $s3;
                }
            }

            if ($s2 !== false) {
                $s3 = self::parseSpace();
                if ($s3 !== false) {
                    $result = new SegmentMatcher($s2);
                }
            }

            if ($result === false) {
                $s1 = self::parseSpace();
                if ($s1 !== false) {
                    $s2 = self::$instance->data->consumeIf('*');
                    if ($s2 === '' || $s2 === false) {
                        $s2 = false;
                        self::fail('*');
                    }

                    if ($s2 !== false) {
                        $s3 = self::parseSpace();
                        if ($s3 !== false) {
                            $result = new TrueMatcher($s2);
                        }
                    }
                }
            }
        }

        if (self::$debug === true) {
            echo "parseSegment Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseSelector(): Matcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;

        $s1 = self::parseComposite();
        if ($s1 !== false) {
            $s2 = self::parseSpace();
            if ($s2 !== false) {
                $s3 = self::$instance->data->consumeIf(',');
                if ($s3 === '' || $s3 === false) {
                    $s3 = false;
                    self::fail(',');
                }

                if ($s3 !== false) {
                    $s4 = self::parseSpace();
                    if ($s4 !== false) {
                        $s5 = self::parseSelector();
                        $result = ($s5 === false) ? $s1 : new OrMatcher($s1, $s5);
                    }
                }
            }
        }

        if (self::$debug === true) {
            echo "parseSelector Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseScope(): ScopeMatcher|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = false;

        $s1 = self::parseSegment();
        if ($s1 !== false) {
            $s2 = [$s1];
            do {
                $s3 = false;

                $s4 = self::$instance->data->consumeIf('.');
                if ($s4 === '' || $s4 === false) {
                    $s4 = false;
                    self::fail('.');
                }

                if ($s4 !== false) {
                    $s3 = self::parseSegment();
                    if ($s3 !== false) {
                        $s2[] = $s3;
                    }
                }
            } while ($s3 !== false);

            $result = new ScopeMatcher(...$s2);
        }

        if (self::$debug === true) {
            echo "parseScope Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }

    protected static function parseSpace(): string|false {
        if (self::$debug === true) {
            self::debug();
        }

        $result = self::$instance->data->consumeWhile(" \t");
        if ($result === false) {
            self::fail(' \t');
        }

        if (self::$debug === true) {
            echo "parseSpace Result: " . var_export($result, true) . "\n";
        }

        return $result;
    }


    protected static function debug() {
        $message = <<<DEBUG
        ------------------------------
        %s
        Method: %s
        Position: %s
        Char: '%s'

        DEBUG;

        $methodTree = '';
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        array_pop($backtrace);
        foreach ($backtrace as $b) {
            $methodTree = "->{$b['function']}$methodTree";
        }

        printf($message,
            self::$debugCount++,
            ltrim($methodTree, '->'),
            self::$instance->data->position,
            self::$instance->data->peek()
        );
    }

    protected static function fail(string $expected) {
        self::$instance->lastExceptionData = [
            'expected' => $expected,
            'found' => self::$instance->data->peek()
        ];
    }
}
