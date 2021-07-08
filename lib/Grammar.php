<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\CaptureList,
    dW\Lit\Grammar\Exception,
    dW\Lit\Grammar\GrammarInclude,
    dW\Lit\Grammar\InjectionList,
    dW\Lit\Grammar\Pattern,
    dW\Lit\Grammar\PatternList,
    dW\Lit\Grammar\Registry,
    dW\Lit\Grammar\Repository;


/**
 * When the input data is read line-by-line a grammar object is used as a set of
 * instructions as to what to match and highlight
 */
class Grammar {
    use FauxReadOnly;

    protected ?string $_contentRegex;
    protected ?string $_firstLineMatch;
    protected ?InjectionList $_injections;
    protected ?string $_name;
    protected PatternList $_patterns;
    protected ?Repository $_repository;
    protected string $_scopeName;


    public function __construct(string $scopeName, PatternList $patterns, ?string $name = null, ?string $contentRegex = null, ?string $firstLineMatch = null, ?InjectionList $injections = null, ?Repository $repository = null, bool $register = true) {
        $this->_name = $name;
        $this->_scopeName = $scopeName;
        $this->_patterns = $patterns;
        $this->_contentRegex = $contentRegex;
        $this->_firstLineMatch = $firstLineMatch;
        $this->_injections = $injections;
        $this->_repository = $repository;

        if ($register) {
            Registry::set($scopeName, $this);
        }
    }

    /** Parses an Atom JSON grammar and converts to a Grammar object */
    public static function fromJSON(string $jsonPath, bool $register = false): self {
        if (!is_file($jsonPath)) {
            throw new Exception(Exception::JSON_INVALID_FILE, $jsonPath);
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        if ($json === null) {
            throw new Exception(json_last_error() + 200, $jsonPath);
        }

        if (!isset($json['scopeName'])) {
            throw new Exception(Exception::JSON_MISSING_PROPERTY, $jsonPath, 'scopeName');
        }

        if (!isset($json['patterns'])) {
            throw new Exception(Exception::JSON_MISSING_PROPERTY, $jsonPath, 'patterns');
        }

        $name = $json['name'] ?? null;
        $scopeName = $json['scopeName'];
        $contentRegex = (isset($json['contentRegex'])) ? "/{$json['contentRegex']}/" : null;
        $firstLineMatch = (isset($json['firstLineMatch'])) ? "/{$json['firstLineMatch']}/" : null;

        $patterns = self::parseJSONPatternList($json['patterns'], $jsonPath);

        $injections = null;
        if (isset($json['injections'])) {
            $injections = [];
            foreach ($json['injections'] as $key => $injection) {
                $injsections[$key] = (count($injection) === 1 && key($injection) === 'patterns') ?  self::parseJSONPatternList($injection['patterns'], $jsonPath) : self::parseJSONPattern($injection, $jsonPath);
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
                $repository[$key] = (count($r) === 1 && key($r) === 'patterns') ? self::parseJSONPatternList($r['patterns'], $jsonPath) : self::parseJSONPattern($r, $jsonPath);
            }

            if (count($repository) > 0) {
                $repository = new Repository($repository);
            } else {
                $repository = null;
            }
        }

        return new self($scopeName, $patterns, $name, $contentRegex, $firstLineMatch, $injections, $repository, $register);
    }


    protected static function parseJSONPattern(array $pattern, string $jsonPath): GrammarInclude|Pattern|null {
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
                    if (!is_bool($value) || (!is_int($value) && ($value !== 0 && $value !== 1))) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Boolean, 0, or 1', 'applyEndPatternLast', gettype($value), $jsonPath);
                    }

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
                    if (!is_array($value)) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Array', $key, gettype($value), $jsonPath);
                    }

                    if (count($value) === 0) {
                        continue 2;
                    }

                    $k = array_map(function($n) use ($jsonPath) {
                        if (is_int($n)) {
                            return $n;
                        }

                        if (strspn($n, '0123456789') !== strlen($n)) {
                            throw new Exception(Exception::JSON_INVALID_TYPE, 'Integer', 'capture list index', $n, $jsonPath);
                        }

                        return (int)$n;
                    }, array_keys($value));

                    $v = array_map(function($n) use ($jsonPath) {
                        return (count($n) === 1 && key($n) === 'patterns') ? self::parseJSONPatternList($n['patterns'], $jsonPath) : self::parseJSONPattern($n, $jsonPath);
                    }, array_values($value));

                    $p[$key] = new CaptureList(array_combine($k, $v));
                    $modified = true;
                break;
                case 'patterns':
                    if (!is_array($value)) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Array', $key, gettype($value), $jsonPath);
                    }

                    $p[$key] = self::parseJSONPatternList($value, $jsonPath);
                    $modified = true;
                break;
            }
        }

        return ($modified) ? new Pattern(...$p) : null;
    }

    protected static function parseJSONPatternList(array $list, string $jsonPath): ?PatternList {
        $result = [];
        foreach ($list as $pattern) {
            $p = self::parseJSONPattern($pattern, $jsonPath);
            if ($p !== null) {
                $result[] = $p;
            }
        }

        return (count($result) > 0) ? new PatternList(...$result) : null;
    }
}