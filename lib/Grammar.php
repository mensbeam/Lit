<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace dW\Lit;

interface Grammar {
    public static array $fileTypes;
    public static string $firstLineMatch;
    public static string $name;
    public static GrammarPattern $patterns;
    public static GrammarRepository $repository;
    public static string $scopeName;
}