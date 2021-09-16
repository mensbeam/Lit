<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Scope;

class Path extends Node {
    protected int $_anchor;
    protected array $_scopes = [];

    const ANCHOR_NONE = 0;
    const ANCHOR_START = 1;
    const ANCHOR_END = 2;
    const ANCHOR_BOTH = 3;


    public function __construct(int $anchor, Scope ...$scopes) {
        if ($anchor < 0 || $anchor > 3) {
            throw new \Exception("Anchor must be a value between 0 and 3.\n");
        }

        $this->_anchor = $anchor;
        $this->_scopes = $scopes;
    }


    public function matches(array $scopes): bool {
        // TODO: Handle anchors; while they are parsed they're not factored in when
        // matching because I can't find any documentation anywhere on what they do, and
        // my brain can't tie itself into knots to read that part of the original C++.

        $index = 0;
        $cur = $this->_scopes[$index];
        foreach ($scopes as $s) {
            if (is_string($s)) {
                $s = Parser::parseScope($s);
            } elseif (!$s instanceof Scope) {
                throw new \Exception('Argument $scopes must be an array of strings or instances of '. __NAMESPACE__. "\\Scope.\n");
            }

            if ($cur->matches($s)) {
                $cur = $this->_scopes[++$index] ?? null;
            }

            if ($cur === null) {
                return true;
            }
        }

        return false;
    }


    public function __toString(): string {
        $result = '';

        if ($this->_anchor === self::ANCHOR_START || $this->_anchor === self::ANCHOR_BOTH) {
            $result .= '^';
        }

        $result .= implode(' ', $this->_scopes);

        if ($this->_anchor === self::ANCHOR_END || $this->_anchor === self::ANCHOR_BOTH) {
            $result .= '$';
        }

        return $result;
    }
}
