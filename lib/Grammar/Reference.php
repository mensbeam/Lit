<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Grammar;


/** Acts as a catch-all type for references */
abstract class Reference extends Rule {
    protected string $_ownerGrammarScopeName;


    public function __construct(string $ownerGrammarScopeName) {
        $this->_ownerGrammarScopeName = $ownerGrammarScopeName;
    }
}