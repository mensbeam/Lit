<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

/**
 * An immutable list of rules which can be included from other places in the
 * grammar; The key is the name of the rule and the value is the actual rule.
 */
class Repository extends NamedRuleListList {}