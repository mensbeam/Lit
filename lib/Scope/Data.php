<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Highlighter\Scope;

class Data {
    protected array $data;

    protected int $_position = -1;
    protected int $endPosition;

    public function __construct(string $data) {
        preg_match_all('/[BLR]:|[A-Za-z0-9-+_\*\.]+|[\,\|\-\(\)&]/', $data, $matches, PREG_OFFSET_CAPTURE);
        $this->data = $matches[0] ?? [];
        $this->endPosition = count($this->data) - 1;
    }

    public function consume(): string|bool {
        if ($this->_position === $this->endPosition) {
            return false;
        }

        return $this->data[++$this->_position][0];
    }

    public function offset(): int|bool {
        if ($this->_position > $this->endPosition) {
            return false;
        }

        return $this->data[$this->_position][1];
    }

    public function peek(): string|bool {
        if ($this->_position === $this->endPosition) {
            return false;
        }

        return $this->data[$this->_position + 1][0];
    }

    public function __get(string $name) {
        if ($name === 'position') {
            return $this->_position;
        }
    }
}
