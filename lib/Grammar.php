<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\{
    BaseReference,
    Exception,
    GrammarReference,
    Pattern,
    Reference,
    RepositoryReference,
    SelfReference
};


/**
 * When the input data is read line-by-line a grammar object is used as a set of
 * instructions as to what to match and highlight
 */
class Grammar {
    use FauxReadOnly;
    protected ?string $_contentName;
    protected ?array $_injections;
    protected ?string $_name;
    protected ?array $_patterns;
    protected ?array $_repository;
    protected ?string $_scopeName;

    protected const ESCAPE_SLASHES_REGEX = '/(?<!\\\)\//S';
    protected const LONG_CHARACTER_CODE_REGEX = '/\\\x\{([0-9A-Fa-f]+)\}/S';


    public function __construct(?string $scopeName = null, ?array $patterns = null, ?string $name = null, ?array $injections = null, ?array $repository = null) {
        $this->_name = $name;
        $this->_scopeName = $scopeName;
        $this->_patterns = $patterns;
        $this->_injections = $injections;
        $this->_repository = $repository;
    }


    /** Imports an Atom JSON grammar into the Grammar object */
    public function loadJSON(string $filename) {
        if (!is_file($filename)) {
            throw new Exception(Exception::JSON_INVALID_FILE, $filename);
        }

        $json = json_decode(file_get_contents($filename), true);
        if ($json === null) {
            throw new Exception(json_last_error() + 200, $filename);
        }

        if (!isset($json['scopeName'])) {
            throw new Exception(Exception::JSON_MISSING_PROPERTY, $filename, 'scopeName');
        }

        if (!isset($json['patterns'])) {
            throw new Exception(Exception::JSON_MISSING_PROPERTY, $filename, 'patterns');
        }

        $this->_name = $json['name'] ?? null;
        $this->_scopeName = $json['scopeName'];

        $repository = null;
        if (isset($json['repository'])) {
            $respository = [];
            foreach ($json['repository'] as $key => $r) {
                $repository[$key] = $this->parseJSONPattern($r, $filename);
            }
            $repository = (count($repository) > 0) ? $repository : null;
        }
        $this->_repository = $repository;

        $this->_patterns = $this->parseJSONPatternArray($json['patterns'], $filename);

        $injections = null;
        if (isset($json['injections'])) {
            $injections = [];
            foreach ($json['injections'] as $key => $injection) {
                $injections[$key] = $this->parseJSONPattern($injection, $filename, true);
            }
            $injections = (count($injections) > 0) ? $injections : null;
        }
        $this->_injections = $injections;
    }


    protected function parseJSONPattern(array $pattern, string $filename, bool $isInjection = false): Pattern|Reference|null {
        if (isset($pattern['include'])) {
            if ($pattern['include'][0] === '#') {
                return new RepositoryReference(substr($pattern['include'], 1), $this->_scopeName);
            } elseif ($pattern['include'] === '$base') {
                return new BaseReference($this->_scopeName);
            } elseif ($pattern['include'] === '$self') {
                return new SelfReference($this->_scopeName);
            } else {
                return new GrammarReference($pattern['include'], $this->_scopeName);
            }
        }

        $p = [
            'name' => null,
            'contentName' => null,
            'match' => null,
            'patterns' => null,
            'captures' => null,
            'beginPattern' => false,
            'endPattern' => (isset($pattern['endPattern']) && $pattern['endPattern']),
            'injection' => $isInjection
        ];

        $modified = false;

        $applyEndPatternLast = false;
        if (isset($pattern['applyEndPatternLast'])) {
            $applyEndPatternLast = $pattern['applyEndPatternLast'];
            if (!is_bool($applyEndPatternLast) || (!is_int($applyEndPatternLast) && ($applyEndPatternLast !== 0 && $applyEndPatternLast !== 1))) {
                throw new Exception(Exception::JSON_INVALID_TYPE, 'Boolean, 0, or 1', 'applyEndPatternLast', gettype($applyEndPatternLast), $filename);
            }

            $applyEndPatternLast = (bool)$applyEndPatternLast;
        }

        // Begin and end matches are handled in this implementation by parsing begin
        // matches as regular matches and appending the end match as a pattern
        // to the the pattern's patterns with an end pattern flag turned on
        // which is used to exit matching.
        if (isset($pattern['begin'])) {
            if (!isset($pattern['end'])) {
                throw new Exception(Exception::JSON_MISSING_PROPERTY, $filename, 'end');
            }

            $modified = true;

            $endCaptures = null;
            if (isset($pattern['endCaptures'])) {
                $endCaptures = $pattern['endCaptures'];
            } elseif (isset($pattern['captures'])) {
                $endCaptures = $pattern['captures'];
            }

            if (isset($pattern['beginCaptures'])) {
                $pattern['captures'] = $pattern['beginCaptures'];
            } elseif (isset($pattern['captures'])) {
                $pattern['captures'] = $pattern['captures'];
            }

            $endPattern = [
                'match' => $pattern['end'],
                'endPattern' => true
            ];

            if ($endCaptures !== null) {
                $endPattern['captures'] = $endCaptures;
            }

            if (isset($pattern['patterns'])) {
                if ($applyEndPatternLast) {
                    $pattern['patterns'][] = $endPattern;
                } else {
                    array_unshift($pattern['patterns'], $endPattern);
                }
            } else {
                $pattern['patterns'] = [ $endPattern ];
            }
        }

        foreach ($pattern as $key => $value) {
            switch ($key) {
                case 'captures':
                    if (!is_array($value)) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Array', $key, gettype($value), $filename);
                    }

                    if (count($value) === 0) {
                        continue 2;
                    }

                    $k = array_map(function($n) use ($filename) {
                        if (is_int($n)) {
                            return $n;
                        }

                        if (strspn($n, '0123456789') !== strlen($n)) {
                            throw new Exception(Exception::JSON_INVALID_TYPE, 'Integer', 'capture list index', $n, $filename);
                        }

                        return (int)$n;
                    }, array_keys($value));

                    $v = array_map(function($n) use ($filename) {
                        return $this->parseJSONPattern($n, $filename);
                    }, array_values($value));

                    $p[$key] = array_combine($k, $v);
                    $modified = true;
                break;
                case 'begin':
                    $p['beginPattern'] = true;
                case 'match':
                    // Escape forward slashes that aren't escaped in regexes.
                    $value = preg_replace(self::ESCAPE_SLASHES_REGEX, '\/', $value);
                    // Fix oniguruma long character codes.
                    $value = preg_replace_callback(self::LONG_CHARACTER_CODE_REGEX, function($matches) {
                        return "\x{" . (((int)base_convert($matches[1], 16, 10) > 0x10ffff) ? '10ffff' : $matches[1]) . "}";
                    }, $value);

                    $p['match'] = "/$value/Su";

                    $modified = true;
                break;
                case 'contentName':
                case 'name':
                    $p[$key] = $value;
                    $modified = true;
                break;
                case 'patterns':
                    if (!is_array($value)) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Array', $key, gettype($value), $filename);
                    }

                    $p[$key] = $this->parseJSONPatternArray($value, $filename);
                    $modified = true;
                break;
            }
        }

        return ($modified) ? new Pattern(...$p) : null;
    }

    protected function parseJSONPatternArray(array $list, string $filename): ?array {
        $result = [];
        foreach ($list as $pattern) {
            $p = $this->parseJSONPattern($pattern, $filename);
            if ($p !== null) {
                $result[] = $p;
            }
        }

        return (count($result) > 0) ? $result : null;
    }
}