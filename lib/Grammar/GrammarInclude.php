<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly;

/**
 * This allows for referencing a different language, recursively referencing the
 * grammar itself, or a rule declared in the file's repository.
 */
class GrammarInclude {
    use FauxReadOnly;

    const BASE_TYPE = 0;
    const REPOSITORY_TYPE = 1;
    const SCOPE_TYPE = 2;
    const SELF_TYPE = 3;

    protected ?string $_name;
    protected int $_type;


    public function __construct(string $string) {
        if ($string[0] === '#') {
            $this->_type = self::REPOSITORY_TYPE;
            $this->_name = substr($string, 1);
        } elseif ($string === '$base') {
            $this->_type = self::BASE_TYPE;
        } elseif ($string === '$self') {
            $this->_type = self::SELF_TYPE;
        } else {
            $this->_type = self::SCOPE_TYPE;
            $this->_name = $string;
        }
    }
}