<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;


class Data {
    use FauxReadOnly;
    // True if on the first line
    protected bool $_firstLine = true;
    protected \Generator $generator;
    // True if on the last line.
    protected bool $_lastLine = false;
    // Some matches will check for the last line before the final newline, so this
    // will be true if on the line before the final newline or if on the last line
    // if there isn't an extra newline at the end of the string.
    protected bool $_lastLineBeforeFinalNewLine = false;


    public function __construct(string $data) {
        $this->generator = $this->lineGenerator($data);
    }


    public function get(): \Generator {
        return $this->generator;
    }


    protected function lineGenerator(string $string): \Generator {
        $string = explode("\n", $string);
        $lastLineIndex = count($string) - 1;
        $lastLineBeforeFinalNewLineIndex = ($string[$lastLineIndex] === '') ? $lastLineIndex - 1 : $lastLineIndex;

        foreach ($string as $lineNumber => $line) {
            $this->_lastLine = ($lineNumber === $lastLineIndex);
            $this->_lastLineBeforeFinalNewLine = ($lineNumber === $lastLineBeforeFinalNewLineIndex);
            yield $lineNumber + 1 => $line;
            $this->_firstLine = false;
        }
    }
}