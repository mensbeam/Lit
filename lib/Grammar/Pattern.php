<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/** Contains patterns responsible for matching a portion of the document */
class Pattern extends Rule {
    protected bool $_applyEndPatternLast = false;
    protected ?string $_begin;
    protected ?CaptureList $_beginCaptures;
    protected ?CaptureList $_captures;
    protected ?string $_contentName;
    protected ?string $_end;
    protected ?CaptureList $_endCaptures;
    protected ?string $_match;
    protected ?string $_name;
    protected ?PatternList $_patterns;


    public function __construct(?string $name = null, ?string $contentName = null, ?string $begin = null, ?string $end = null, ?string $match = null, ?PatternList $patterns = null, ?CaptureList $captures = null, ?CaptureList $beginCaptures = null, ?CaptureList $endCaptures = null, bool $applyEndPatternLast = false) {
        $this->_name = $name;
        $this->_contentName = $contentName;
        $this->_begin = $begin;
        $this->_end = $end;
        $this->_match = $match;
        $this->_patterns = $patterns;
        $this->_captures = $captures;
        $this->_beginCaptures = $beginCaptures;
        $this->_endCaptures = $endCaptures;
        $this->_applyEndPatternLast = $applyEndPatternLast;
    }
}