<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly;
use dW\Lit\Grammar;

class Pattern {
    use FauxReadOnly;

    protected bool $_applyEndPatternLast = false;
    protected ?string $_begin;
    protected ?array $_beginCaptures;
    protected ?array $_captures;
    protected ?string $_contentName;
    protected ?string $_end;
    protected ?array $_endCaptures;
    protected ?string $_match;
    protected ?string $_name;
    protected ?PatternList $_patterns;


    public function __construct(?string $name = null, ?string $contentName = null, ?string $begin = null, ?string $end = null, ?string $match = null, ?PatternList $patterns = null, ?string $include = null, ?array $captures = null, ?array $beginCaptures = null, ?array $endCaptures = null) {
        $this->_name = $name;
        $this->_contentName = $contentName;
        $this->_begin = $begin;
        $this->_end = $end;
        $this->_match = $match;

        $this->_patterns = $patterns;
        $this->_captures = $captures;
        $this->_beginCaptures = $beginCaptures;
        $this->_endCaptures = $endCaptures;
    }
}