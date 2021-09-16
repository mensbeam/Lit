<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace MensBeam\Lit;


class Data {
    use FauxReadOnly;
    // True if on the first line
    protected bool $_firstLine = true;
    // The stored generator
    protected \Generator $generator;
    // True if on the last line.
    protected bool $_lastLine = false;
    // Some matches will check for the last line before the final newline, so this
    // will be true if on the line before the final newline or if on the last line
    // if there isn't an extra newline at the end of the string.
    protected bool $_lastLineBeforeFinalNewLine = false;
    // The input string split into an array by newline
    protected array $lines = [];
    // The length of the data array
    protected int $linesLength = 0;


    public function __construct(string $data) {
        $this->lines = explode("\n", $data);
        $this->linesLength = count($this->lines);
        $this->generator = $this->lineGenerator();
    }

    public function get(): \Generator {
        return $this->generator;
    }


    protected function lineGenerator(): \Generator {
        $lastLineIndex = $this->linesLength - 1;
        $lastLineBeforeFinalNewLineIndex = ($this->lines[$lastLineIndex] === '') ? $lastLineIndex - 1 : $lastLineIndex;

        foreach ($this->lines as $lineNumber => $line) {
            $this->_lastLine = ($lineNumber === $lastLineIndex);
            $this->_lastLineBeforeFinalNewLine = ($lineNumber === $lastLineBeforeFinalNewLineIndex);
            yield $lineNumber + 1 => $line;
            $this->_firstLine = false;
        }
    }
}