<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;

/**
 * Base references in this implementation are simply used as a type. The
 * tokenizer stores the base grammar because it's simply the lowest item on the
 * stack and simply uses it when encountering a Base reference.
 */
class BaseReference extends Reference {}