<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Tokenizer {
    protected array $matches = [];
    protected int $position = 0;

    public function __construct(string $scope) {
        preg_match_all('/([LR]:|[\w\.:][\w\.:\-]*|[\,\|\-\(\)])/', $scope, $matches);
        $this->matches = $matches[1];
    }


    public function next(): string|false {
        if (count($this->matches) === 0) {
            return false;
        }

        $result = $this->matches[$this->position] ?? false;

        if ($result !== false) {
            $this->position++;
        }

        return $result;
    }

    public function tokenIsIdentifier(): bool {
        if (!isset($this->matches[$this->position])) {
            return false;
        }

        return (!!$this->matches[$this->position] && !!preg_match('/[\w\.:]+/', $this->matches[$this->position]));
    }
}