<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\CaptureList,
    dW\Lit\Grammar\GrammarInclude,
    dW\Lit\Grammar\InjectionList,
    dW\Lit\Grammar\Pattern,
    dW\Lit\Grammar\PatternList,
    dW\Lit\Grammar\Repository;

class Grammar {
    use FauxReadOnly;

    protected ?string $_contentRegex;
    protected ?string $_firstLineMatch;
    protected ?InjectionList $_injections;
    protected ?string $_name;
    protected PatternList $_patterns;
    protected ?Repository $_repository;
    protected string $_scopeName;


    public function __construct(string $scopeName, PatternList $patterns, ?string $name = null, ?string $contentRegex = null, ?string $firstLineMatch = null, ?InjectionList $injections = null, ?Repository $repository = null) {
        $this->_name = $name;
        $this->_scopeName = $scopeName;
        $this->_patterns = $patterns;
        $this->_contentRegex = $contentRegex;
        $this->_firstLineMatch = $firstLineMatch;
        $this->_injections = $injections;
        $this->_repository = $repository;
    }


    public static function fromJSON(string $jsonPath): self {
        assert(is_file($jsonPath), new \Exception("\"$jsonPath\" is either not a file or you do not have permission to read the file\n"));

        $json = json_decode(file_get_contents($jsonPath), true);
        if ($json === null) {
            $message = "Parsing \"$jsonPath\" failed with the following error: ";
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $message .= 'Maximum stack depth exceeded';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    $message .= 'Underflow or mode mismatch';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    $message .= 'Unexpected control character found';
                break;
                case JSON_ERROR_SYNTAX:
                    $message .= 'Syntax error, malformed JSON';
                break;
                case JSON_ERROR_UTF8:
                    $message .= 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
                default:
                    $message .= 'Unknown error';
                break;
            }

            throw new \Exception("$message\n");
        }

        assert(isset($json['scopeName']), new \Exception("\"$jsonPath\" does not have the required scopeName property"));
        assert(isset($json['patterns']), new \Exception("\"$jsonPath\" does not have the required patterns property"));

        $name = $json['name'] ?? null;
        $scopeName = $json['scopeName'];
        $contentRegex = (isset($json['contentRegex'])) ? "/{$json['contentRegex']}/" : null;
        $firstLineMatch = (isset($json['firstLineMatch'])) ? "/{$json['firstLineMatch']}/" : null;

        $patterns = self::parseJSONPatternList($json['patterns']);

        $injections = null;
        if (isset($json['injections'])) {
            $injections = [];
            foreach ($json['injections'] as $key => $injection) {
                $injsections[$key] = (count($injection) === 1 && key($injection) === 'patterns') ?  self::parseJSONPatternList($injection['patterns']) : self::parseJSONPattern($injection);
            }

            if (count($injections) > 0) {
                $injections = new InjectionList($injections);
            } else {
                $injections = null;
            }
        }

        $repository = null;
        if (isset($json['repository'])) {
            $respository = [];
            foreach ($json['repository'] as $key => $r) {
                $repository[$key] = (count($r) === 1 && key($r) === 'patterns') ? self::parseJSONPatternList($r['patterns']) : self::parseJSONPattern($r);
            }

            if (count($repository) > 0) {
                $repository = new Repository($repository);
            } else {
                $repository = null;
            }
        }

        return new self($scopeName, $patterns, $name, $contentRegex, $firstLineMatch, $injections, $repository);
    }


    protected static function parseJSONPattern(array $pattern): GrammarInclude|Pattern|null {
        if (array_keys($pattern) === [ 'include' ]) {
            return new GrammarInclude($pattern['include']);
        }

        $p = [
            'name' => null,
            'contentName' => null,
            'begin' => null,
            'end' => null,
            'match' => null,
            'patterns' => null,
            'captures' => null,
            'beginCaptures' => null,
            'endCaptures' => null,
            'applyEndPatternLast' => false
        ];

        $modified = false;
        foreach ($pattern as $key => $value) {
            switch ($key) {
                case 'applyEndPatternLast':
                    assert(is_bool($value) || (is_int($value) && ($value === 0 || $value === 1)), new \Exception("The value for applyEndPatternLast must be either a boolean, 0, or 1\n"));
                    $value = (bool)$value;
                case 'name':
                case 'contentName':
                    $p[$key] = $value;
                    $modified = true;
                break;
                case 'begin':
                case 'end':
                case 'match':
                    $p[$key] = "/$value/";
                    $modified = true;
                break;
                case 'captures':
                case 'beginCaptures':
                case 'endCaptures':
                    assert(is_array($value), new \Exception("Array value expected for '$key', found " . gettype($value) . "\n"));
                    if (count($value) === 0) {
                        continue 2;
                    }

                    $kk = array_keys($value);
                    $v = array_values($value);

                    // Skipping that bad three k variable name here... :)
                    foreach ($kk as &$kkkk) {
                        if (is_int($kkkk)) {
                            continue;
                        }

                        assert(strspn($kkkk, '0123456789') === strlen($kkkk), new \Exception("\"$kkkk\" is not castable to an integer for use in a capture list\n"));
                        $kkk = (int)$kkkk;
                    }

                    $v = array_map(function ($n) {
                        return (count($n) === 1 && key($n) === 'patterns') ? self::parseJSONPatternList($n['patterns']) : self::parseJSONPattern($n);
                    }, $v);

                    $p[$key] = new CaptureList(array_combine($kk, $v));
                    $modified = true;
                break;
                case 'patterns':
                    assert(is_array($value), new \Exception("Array value expected for '$key', found " . gettype($value) . "\n"));
                    $p[$key] = self::parseJSONPatternList($value);
                    $modified = true;
                break;
            }
        }

        return ($modified) ? new Pattern(...$p) : null;
    }


    protected static function parseJSONPatternList(array $list): ?PatternList {
        $result = [];
        foreach ($list as $pattern) {
            $p = self::parseJSONPattern($pattern);
            if ($p !== null) {
                $result[] = $p;
            }
        }

        return (count($result) > 0) ? new PatternList(...$result) : null;
    }
}