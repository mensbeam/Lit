<?php
/** @license MIT
 * Copyright 2021 Dustin Wilson et al.
 * See LICENSE file for details */

declare(strict_types=1);
namespace dW\Lit;
use dW\Lit\Grammar\InjectionList,
    dW\Lit\Grammar\PatternList,
    dW\Lit\Grammar\Repository;

class Grammar {
    use FauxReadOnly;

    protected string|null $_contentRegex;
    protected string|null $_firstLineMatch;
    protected InjectionList|null $_injections;
    protected string $_name;
    protected PatternList $_patterns;
    protected Repository|null $_repository;
    protected string $_scopeName;


    public function __construct(string $name, string $scopeName, PatternList $patterns, string|null $contentRegex = null, string|null $firstLineMatch = null, InjectionList|null $injections = null, Repository|null $repository = null) {
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

        $json = json_decode($jsonPath, true);
        assert($json, new \Exception("\"$jsonPath\" is not a valid JSON file.\n"));

        assert(isset($json['name']), new \Exception("\"$jsonPath\" does not have the required name property"));
        assert(isset($json['scopeName']), new \Exception("\"$jsonPath\" does not have the required scopeName property"));
        assert(isset($json['patterns']), new \Exception("\"$jsonPath\" does not have the required patterns property"));

        $name = $json['name'];
        $scopeName = $json['scopeName'];
        $contentRegex = (isset($json['contentRegex'])) ? "/{$json['contentRegex']}/" : null;
        $firstLineMatch = (isset($json['firstLineMatch'])) ? "/{$json['firstLineMatch']}/" : null;

        $patterns = [];
        foreach ($json['patterns'] as $pattern) {
            foreach ($pattern as $key => $p) {

            }
        }

        if (count($patterns) > 0) {
            $patterns = new PatternList(...$patterns);
        } else {
            $patterns = null;
        }

        if (isset($json['injections'])) {
            $injections = [];
            foreach ($json['injections'] as $injection) {

            }

            if (count($injections) > 0) {
                $injections = new InjectionList($injections);
            } else {
                $patterns = null;
            }
        }


        if (isset($json['repository'])) {
            $respository = [];
            foreach ($json['repository'] as $r) {

            }

            if (count($repository) > 0) {
                $repository = new InjectionList($repository);
            } else {
                $repository = null;
            }
        }
        
        return new self($name, $scopeName, $patterns, $contentRegex, $firstLineMatch, $injections, $repository);
    }
}