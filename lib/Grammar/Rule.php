<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly;

/**
 * Abstract class used as a base class for Pattern and Reference classes
 */
abstract class Rule {
    use FauxReadOnly;
}