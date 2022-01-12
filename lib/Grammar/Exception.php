<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lit\Grammar;
use MensBeam\Framework\Exception as FrameworkException;

class Exception extends FrameworkException {
    const JSON_INVALID_FILE = 300;
    const JSON_ERROR_STATE_MISMATCH = 301;
    const JSON_ERROR_CTRL_CHAR = 302;
    const JSON_ERROR_SYNTAX = 303;
    const JSON_ERROR_UTF8 = 304;
    const JSON_ERROR_RECURSION = 305;
    const JSON_ERROR_INF_OR_NAN = 306;
    const JSON_ERROR_UNSUPPORTED_TYPE = 307;
    const JSON_ERROR_INVALID_PROPERTY_NAME = 308;
    const JSON_ERROR_UTF16 = 309;
    const JSON_MISSING_PROPERTY = 310;
    const JSON_INVALID_TYPE = 311;

    const GRAMMAR_MISSING = 400;


    public function __construct(int $code, ...$args) {
        self::$messages = array_replace(parent::$messages, [
            300 => '"%s" is either not a file or you do not have permission to read the file',
            301 => '"%s" is invalid or malformed JSON',
            302 => 'Invalid control character encountered when parsing "%s"',
            303 => 'Syntax error, malformed JSON when parsing "%s"',
            304 => 'Malformed UTF-8 characters, possibly incorrectly encoded when parsing "%s"',
            305 => 'One or more recursive references could not be encoded when parsing "%s"',
            306 => 'One or more NAN or INF values could not be encoded when parsing "%s"',
            307 => 'Unsupported type encountered when parsing "%s"',
            308 => 'Invalid property name encountered when parsing "%s"',
            309 => 'Malformed UTF-16 characters, possibly incorrectly encoded when parsing "%s"',
            310 => '"%1$s" does not have the required %2$s property',
            311 => '%1$s expected for %2$s, found %3$s in "%4$s"',

            400 => 'A grammar for scope %s does not exist; one may be added using GrammarRegistry::set'
        ]);

        parent::__construct($code, ...$args);
    }
}