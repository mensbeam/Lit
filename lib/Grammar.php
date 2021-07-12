<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\BaseReference,
    dW\Lit\Grammar\CaptureList,
    dW\Lit\Grammar\Exception,
    dW\Lit\Grammar\GrammarReference,
    dW\Lit\Grammar\InjectionList,
    dW\Lit\Grammar\Pattern,
    dW\Lit\Grammar\PatternList,
    dW\Lit\Grammar\Reference,
    dW\Lit\Grammar\Registry,
    dW\Lit\Grammar\Repository,
    dW\Lit\Grammar\RepositoryReference,
    dW\Lit\Grammar\SelfReference;


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
    protected ?\WeakReference $_ownerGrammar;
    protected ?PatternList $_patterns;
    protected ?Repository $_repository;
    protected ?string $_scopeName;


    public function __construct(?string $scopeName = null, ?PatternList $patterns = null, ?string $name = null, ?string $contentRegex = null, ?string $firstLineMatch = null, ?InjectionList $injections = null, ?Repository $repository = null, ?Grammar $ownerGrammar = null) {
        $this->_name = $name;
        $this->_scopeName = $scopeName;
        $this->_patterns = $patterns;
        $this->_contentRegex = $contentRegex;
        $this->_firstLineMatch = $firstLineMatch;
        $this->_injections = $injections;
        $this->_repository = $repository;
        $this->_ownerGrammar = (is_null($ownerGrammar)) ? null : \WeakReference::create($ownerGrammar);
    }


    /** Clones the supplied grammar with this grammar set as its owner grammar */
    public function adoptGrammar(self $grammar): self {
        return new self($grammar->name, $grammar->scopeName, $grammar->patterns, $grammar->contentRegex, $grammar->firstLineMatch, $grammar->injections, $this, $grammar->repository);
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
        $this->_contentRegex = (isset($json['contentRegex'])) ? "/{$json['contentRegex']}/" : null;
        $this->_firstLineMatch = (isset($json['firstLineMatch'])) ? "/{$json['firstLineMatch']}/" : null;

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


    protected function parseJSONPattern(array $pattern, string $filename): Pattern|Reference|\WeakReference|null {
        if (isset($pattern['include'])) {
            if ($pattern['include'][0] === '#') {
                return new RepositoryReference(substr($pattern['include'], 1), $this);
            } elseif ($pattern['include'] === '$base') {
                return new BaseReference($this);
            } elseif ($pattern['include'] === '$self') {
                return \WeakReference::create($this);
            } else {
                return new GrammarReference($pattern['include'], $this);
            }
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
                        throw new Exception(Exception::JSON_INVALID_TYPE, 'Boolean, 0, or 1', 'applyEndPatternLast', gettype($value), $filename);
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

    protected function parseJSONPatternList(array $list, string $filename): Pattern|PatternList|null {
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