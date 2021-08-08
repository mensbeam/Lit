<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit\Grammar;
use dW\Lit\FauxReadOnly;

/** Immutable named pattern list used for repositories and injection lists. */
abstract class NamedPatternList extends ImmutableList {
    use FauxReadOnly;

    public function __construct(array $array) {
        // This shit is here because PHP doesn't have array types or generics :)
        foreach ($array as $k => $v) {
            if (!is_string($k)) {
                throw new Exception(Exception::LIST_INVALID_TYPE, 'String', 'supplied array index', gettype($k));
            }

            if (!$v instanceof Pattern && !$v instanceof PatternList && !$v instanceof Reference) {
                $type = gettype($v);
                if ($type === 'object') {
                    $type = get_class($v);
                }

                throw new Exception(Exception::LIST_INVALID_TYPE,  __NAMESPACE__.'\Pattern, '.__NAMESPACE__.'\PatternList, '.__NAMESPACE__.'\Reference', 'supplied array value', $type);
            }
        }

        $this->storage = $array;
    }
}