<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\{
    BaseReference,
    CaptureList,
    ChildGrammarRegistry,
    Exception,
    FauxReadOnly,
    GrammarReference,
    InjectionList,
    Node,
    Pattern,
    PatternList,
    Reference,
    Repository,
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
    protected ?string $_firstLineMatch;
    protected ?InjectionList $_injections;
    protected ?string $_name;
    protected ?\WeakReference $_ownerGrammar;
    protected ?PatternList $_patterns;
    protected ?Repository $_repository;
    protected ?string $_scopeName;


    public function __construct(?string $scopeName = null, ?PatternList $patterns = null, ?string $name = null, ?string $firstLineMatch = null, ?InjectionList $injections = null, ?Repository $repository = null, ?Grammar $ownerGrammar = null) {
        $this->_name = $name;
        $this->_scopeName = $scopeName;
        $this->_patterns = $patterns;
        $this->_firstLineMatch = $firstLineMatch;
        $this->_injections = $injections;
        $this->_repository = $repository;
        $this->_ownerGrammar = (is_null($ownerGrammar)) ? null : \WeakReference::create($ownerGrammar);
    }

    // Used when adopting to change the $ownerGrammar property.
    public function withOwnerGrammar(Grammar $ownerGrammar): self {
        if ($new = ChildGrammarRegistry::get($this->_scopeName, $ownerGrammar)) {
            return $new;
        }

        $new = clone $this;
        if ($new->_patterns !== null) {
            $new->_patterns = $new->_patterns->withOwnerGrammar($new);
        }

        if ($new->_injections !== null) {
            $new->_injections = $new->_injections->withOwnerGrammar($new);
        }

        if ($new->_repository !== null) {
            $new->_repository = $new->_repository->withOwnerGrammar($new);
        }

        $new->_ownerGrammar = \WeakReference::create($ownerGrammar);

        ChildGrammarRegistry::set($this->_scopeName, $new);
        return $new;
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

        if (isset($json['firstLineMatch'])) {
            $value = str_replace('/', '\/', $json['firstLineMatch']);
            $this->_firstLineMatch = $value;
        } else {
            $this->_firstLineMatch = null;
        }

        $repository = null;
        if (isset($json['repository'])) {
            $respository = [];
            foreach ($json['repository'] as $key => $r) {
                $repository[$key] = $this->parseJSONPattern($r, $filename);
            }

            if (count($repository) > 0) {
                $repository = new Repository($repository);
            } else {
                $repository = null;
            }
        }
        $this->_repository = $repository;

        $this->_patterns = $this->parseJSONPatternList($json['patterns'], $filename);

        $injections = null;
        if (isset($json['injections'])) {
            $injections = [];
            foreach ($json['injections'] as $key => $injection) {
                $injections[$key] = $this->parseJSONPattern($injection, $filename);
            }

            if (count($injections) > 0) {
                $injections = new InjectionList($injections);
            } else {
                $injections = null;
            }
        }
        $this->_injections = $injections;
    }


    protected function parseJSONPattern(array $pattern, string $filename): Pattern|Reference|null {
        if (isset($pattern['include'])) {
            if ($pattern['include'][0] === '#') {
                return new RepositoryReference(substr($pattern['include'], 1), $this);
            } elseif ($pattern['include'] === '$base') {
                return new BaseReference($this);
            } elseif ($pattern['include'] === '$self') {
                return new SelfReference($this);
            } else {
                return new GrammarReference($pattern['include'], $this);
            }
        }

        $p = [
            'ownerGrammar' => $this,
            'name' => null,
            'match' => null,
            'patterns' => null,
            'captures' => null,
            'endPattern' => false
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
        // matches as regular matches and appending the end match as a pattern to the
        // end of the pattern's patterns.
        if (isset($pattern['begin'])) {
            if (!isset($pattern['end'])) {
                throw new Exception(Exception::JSON_MISSING_PROPERTY, $filename, 'end');
            }

            $begin = str_replace('/', '\/', $pattern['begin']);
            $p['match'] = "/$begin/u";
            $modified = true;

            if (isset($pattern['beginCaptures'])) {
                $pattern['captures'] = $pattern['beginCaptures'];
            } elseif (isset($pattern['captures'])) {
                $pattern['captures'] = $pattern['captures'];
            }

            $endCaptures = null;
            if (isset($pattern['endCaptures'])) {
                $endCaptures = $pattern['endCaptures'];
            } elseif (isset($pattern['captures'])) {
                $endCaptures = $pattern['captures'];
            }

            $endPattern = [
                'match' => '/' . str_replace('/', '\/', $pattern['end']) . '/u',
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
                case 'name':
                    $p[$key] = $value;
                    $modified = true;
                break;
                case 'match':
                    $value = str_replace('/', '\/', $value);
                    $p['match'] = "/$value/u";
                    $modified = true;
                break;
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

                    $p[$key] = new CaptureList(array_combine($k, $v));
                    $modified = true;
                break;
                case 'patterns':
                    if (!is_array($value)) {
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Array', $key, gettype($value), $filename);
                    }

                    $p[$key] = $this->parseJSONPatternList($value, $filename);
                    $modified = true;
                break;
            }
        }

        return ($modified) ? new Pattern(...$p) : null;
    }

    protected function parseJSONPatternList(array $list, string $filename): ?PatternList {
        $result = [];
        foreach ($list as $pattern) {
            $p = $this->parseJSONPattern($pattern, $filename);
            if ($p !== null) {
                $result[] = $p;
            }
        }

        return (count($result) > 0) ? new PatternList(...$result) : null;
    }
}