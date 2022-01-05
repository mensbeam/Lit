<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit\Scope;
use MensBeam\Framework\FauxReadOnly;

class Node {
    use FauxReadOnly;

    public function getPrefix(array $scopes): ?int {
        return null;
    }
}
