<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;

class Token {
    use FauxReadOnly;
    protected array $_scopes;
    protected string $_text;


    public function __construct(array $scopes, string $text) {
        $this->_scopes = $scopes;
        $this->text = $text;
    }
}