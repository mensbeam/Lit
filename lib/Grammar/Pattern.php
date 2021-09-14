<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;


/** Contains patterns responsible for matching a portion of the document */
class Pattern extends Rule {
    protected bool $_beginPattern = false;
    protected ?array $_captures;
    protected ?string $_contentName;
    protected bool $_endPattern = false;
    protected bool $_injection = false;
    protected ?string $_match;
    protected ?string $_name;
    protected ?array $_patterns;


    public function __construct(?string $name = null, ?string $contentName = null, ?string $match = null, ?array $patterns = null, ?array $captures = null, bool $beginPattern = false, bool $endPattern = false, bool $injection = false) {
        $this->_beginPattern = $beginPattern;
        $this->_name = $name;
        $this->_contentName = $contentName;
        $this->_match = $match;
        $this->_patterns = $patterns;
        $this->_captures = $captures;
        $this->_endPattern = $endPattern;
        $this->_injection = $injection;
    }
}