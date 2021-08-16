<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\Grammar;


/** Contains patterns responsible for matching a portion of the document */
class Pattern extends Rule {
    protected ?array $_captures;
    protected ?string $_contentName;
    protected bool $_endPattern = false;
    protected ?string $_match;
    protected ?string $_name;
    protected \WeakReference $_ownerGrammar;
    protected ?array $_patterns;


    public function __construct(Grammar $ownerGrammar, ?string $name = null, ?string $contentName = null, ?string $match = null, ?array $patterns = null, ?array $captures = null, bool $endPattern = false) {
        $this->_name = $name;
        $this->_contentName = $contentName;
        $this->_match = $match;
        $this->_patterns = $patterns;
        $this->_captures = $captures;
        $this->_endPattern = $endPattern;
        $this->_ownerGrammar = ($ownerGrammar === null) ? null : \WeakReference::create($ownerGrammar);
    }

    // Used when adopting to change the $ownerGrammar property.
    public function withOwnerGrammar(Grammar $ownerGrammar): self {
        $new = parent::withOwnerGrammar($ownerGrammar);
        if ($new->_patterns !== null) {
            foreach ($new->_patterns as &$p) {
                $p = $p->withOwnerGrammar($ownerGrammar);
            }
        }
        
        return $new;
    }
}