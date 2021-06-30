<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly;

class Include {
    use FauxReadOnly;

    const REPOSITORY_TYPE = 0;
    const SCOPE_TYPE = 1;
    const SELF_TYPE = 2;

    protected ?string $name;
    protected int $type;


    public function __construct(string $string) {
        if ($string[0] === '#') {
            $this->type = self::REPOSITORY_TYPE;
            $this->name = substr($string, 1);
        } elseif ($string === '$self') {
            $this->type = self::SELF_TYPE;
        }

        $this->type = self::SCOPE_TYPE;
        $this->name = $string;
    }
}