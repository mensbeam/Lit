<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Data {
    protected array $data;

    protected int $position = 0;
    protected int $endPosition;

    public function __construct(string $data) {
        preg_match('/[LRB]:|[A-Za-z0-9-+_\*\.]+|[\,\|\-\(\)&]/', $data, $matches);
        $this->data = $matches[1] ?? [];
        $this->endPosition = count($this->data);
    }

    public function consume(): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        return $this->data[$this->position++];
    }

    public function peek(): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        return $this->data[$this->position + 1];
    }

    public function unconsume(): bool {
        if ($this->position < 0) {
            return false;
        }

        $this->position--;
        return true;
    }
}
