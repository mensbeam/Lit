<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\Lit\Grammar;

class Exception extends \Exception {
    const INVALID_CODE = 100;
    const UNKNOWN_ERROR = 101;
    const INCORRECT_PARAMETERS_FOR_MESSAGE = 102;
    const UNREACHABLE_CODE = 103;

    const JSON_INVALID_FILE = 200;
    const JSON_ERROR_STATE_MISMATCH = 201;
    const JSON_ERROR_CTRL_CHAR = 202;
    const JSON_ERROR_SYNTAX = 203;
    const JSON_ERROR_UTF8 = 204;
    const JSON_ERROR_RECURSION = 205;
    const JSON_ERROR_INF_OR_NAN = 206;
    const JSON_ERROR_UNSUPPORTED_TYPE = 207;
    const JSON_ERROR_INVALID_PROPERTY_NAME = 208;
    const JSON_ERROR_UTF16 = 209;
    const JSON_MISSING_PROPERTY = 210;
    const JSON_INVALID_TYPE = 211;

    const GRAMMAR_MISSING = 400;

    protected static $messages = [
        100 => 'Invalid error code',
        101 => 'Unknown error; escaping',
        102 => 'Incorrect number of parameters for Exception message; %s expected',
        103 => 'Unreachable code',

        200 => '"%s" is either not a file or you do not have permission to read the file',
        201 => '"%s" is invalid or malformed JSON',
        202 => 'Invalid control character encountered when parsing "%s"',
        203 => 'Syntax error, malformed JSON when parsing "%s"',
        204 => 'Malformed UTF-8 characters, possibly incorrectly encoded when parsing "%s"',
        205 => 'One or more recursive references could not be encoded when parsing "%s"',
        206 => 'One or more NAN or INF values could not be encoded when parsing "%s"',
        207 => 'Unsupported type encountered when parsing "%s"',
        208 => 'Invalid property name encountered when parsing "%s"',
        209 => 'Malformed UTF-16 characters, possibly incorrectly encoded when parsing "%s"',
        210 => '"%1$s" does not have the required %2$s property',
        211 => '%1$s expected for %2$s, found %3$s in "%4$s"',

        300 => 'A grammar for scope %s does not exist; one may be added using GrammarRegistry::set'
    ];

    public function __construct(int $code, ...$args) {
        if (!isset(self::$messages[$code])) {
            throw new self(self::INVALID_CODE);
        }

        $message = self::$messages[$code];
        $previous = null;

        if ($args) {
            // Grab a previous exception if there is one.
            if ($args[0] instanceof \Throwable) {
                $previous = array_shift($args);
            } elseif (end($args) instanceof \Throwable) {
                $previous = array_pop($args);
            }
        }

        // Count the number of replacements needed in the message.
        preg_match_all('/(\%(?:\d+\$)?s)/', $message, $matches);
        $count = count(array_unique($matches[1]));

        // If the number of replacements don't match the arguments then oops.
        if (count($args) !== $count) {
            throw new self(self::INCORRECT_PARAMETERS_FOR_MESSAGE, $count);
        }

        if ($count > 0) {
            // Go through each of the arguments and run sprintf on the strings.
            $message = call_user_func_array('sprintf', [ $message, ...$args ]);
        }

        parent::__construct("$message\n", $code, $previous);
    }
}
