<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Data {
    protected string $data;

    protected int $position = 0;
    protected int $endPosition;

    public function __construct(string $data) {
        $this->data = $data;
        $this->endPosition = strlen($data) - 1;
    }

    public function consume(int $length = 1): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        $stop = $this->position + $length;
        if ($stop >= $this->endPosition) {
            $stop = $this->endPosition;
        }

        $output = '';
        for ($i = $this->position; $i <= $stop; $i++) {
            $output .= $this->data[$this->position++];
        }

        return $output;
    }

    public function consumeIf(string $match): string|bool {
        return $this->consumeWhile($match, 1);
    }

    public function consumeUntil(string $match, $limit = null): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        $length = strcspn($this->data, $match, $this->position + 1, $limit);
        if ($length === 0) {
            return '';
        }

        return $this->consume($length);
    }

    public function consumeWhile(string $match, $limit = null): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        $length = strspn($this->data, $match, $this->position + 1, $limit);
        if ($length === 0) {
            return '';
        }

        return $this->consume($length);
    }

    public function current(): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        return $this->data[$this->position];
    }

    public function peek(int $length = 1): string|bool {
        if ($this->position === $this->endPosition) {
            return false;
        }

        $stop = $this->position + $length;
        if ($stop >= $this->endPosition) {
            $stop = $this->endPosition;
        }

        $output = '';
        for ($i = $this->position; $i <= $stop; $i++) {
            $output .= $this->data[$i];
        }

        return $output;
    }
}
