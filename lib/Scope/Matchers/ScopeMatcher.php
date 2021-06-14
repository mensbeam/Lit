<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class ScopeMatcher extends Matcher {
    protected array $segments;

    public function __construct(SegmentMatcher|TrueMatcher ...$matchers) {
        $this->segments = $matchers;
    }

    public function matches(string $scope): bool {
        $lastDotIndex = 0;
        $nextDotIndex = 0;
        $scopeLen = strlen($scope);

        for ($i = 0, $len = count($this->segments); $i < $len; $i++) {
            $matcherSegment = $this->segments[$i];
            if ($lastDotIndex > $scopeLen) {
                break;
            }

            $nextDotIndex = strpos($scope, '.', $lastDotIndex);
            if ($nextDotIndex === false) {
                $nextDotIndex = $scopeLen;
            }

            $scopeSegment = substr($scope, $lastDotIndex, $nextDotIndex - $lastDotIndex);
            if (!$matcherSegment->matches($scopeSegment)) {
                return false;
            }

            $lastDotIndex = $nextDotIndex + 1;
        }

        return ($i === count($this->segments));
    }

    public function getPrefix(string $scope): string|null|false {
        $scopeSegments = explode('.', $scope);
        if (count($scopeSegments) < count($this->segments)) {
            return false;
        }

        foreach ($this->segments as $index => $segment) {
            if ($segment->matches($scopeSegments[$index])) {
                if ($segment->prefix !== null) {
                    return $segment->prefix;
                }
            }
        }
    }
}
