<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

/**
 * An immutable list of injection pattern rules which allows for creation of a
 * new grammar; instead of applying to an entire file it's instead applied to a
 * specific scope selector.
 */
class InjectionList extends NamedPatternList {}