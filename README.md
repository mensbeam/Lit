[a]: https://atom.io
[b]: https://github.com/atom/highlights
[c]: https://macromates.com
[d]: https://code.mensbeam.com/MensBeam/HTML

# Lit #

Lit is a multilanguage syntax highlighter written in PHP. It takes code as input and returns an HTML pre element containing the code highlighted using span elements with classes based upon tokens in the code. It is loosely based upon [Atom][a]'s [Highlights][b] which is used in the Atom text editor to syntax highlight code. Atom's Highlights is in turn based upon [TextMate][c]'s syntax highlighting using its concepts of scope selectors and common keywords for components of programming languages. Lit is not a port of Atom's Highlights but instead an independent implementation of what I can understand of TextMate's grammar syntax, parsing, and tokenization by analyzing other implementations. It aims to at least have feature parity or better with Atom's Highlights.


## Documentation ##

### dW\\Lit\\Grammar::__construct ###

Creates a new `dW\Lit\Grammar` object.

```php
public function dW\Lit\Grammar::__construct(?string $scopeName = null, ?array $patterns = null, ?string $name = null, ?array $injections = null, ?array $repository = null)
```

#### Parameters ####

In normal usage of the library the parameters won't be used (see `dW\Lit\Grammar::loadJSON` and examples below for more information), but they are listed below for completeness' sake.

***scopeName*** - The scope name of the grammar  
***patterns*** - The list of patterns in the grammar  
***name*** - A human-readable name for the grammar  
***injections*** - The list of injections in the grammar  
***repository*** - The list of repository items in the grammar


### dW\\Lit\\Grammar::loadJSON ###

Imports an Atom JSON grammar into the `dW\Lit\Grammar` object.

```php
public function dW\Lit\Grammar::loadJSON(string $filename)
```

#### Parameters ####

***filename*** - The JSON file to be imported


### dW\\Lit\\GrammarRegistry::clear ###

Clears all grammars from the registry

```php
public static function dW\Lit\GrammarRegistry::clear()
```


### dW\\Lit\\GrammarRegistry::get ###

Retrieves a grammar from the registry

```php
public static function dW\Lit\GrammarRegistry::get(string $scopeName): Grammar|false
```

#### Parameters ####

***scopeName*** - The scope name (eg: text.html.php) of the grammar that is being requested

#### Return Values ####

Returns a `dW\Lit\Grammar` object on success and `false` on failure.


### dW\\Lit\\GrammarRegistry::set ###

Retrieves a grammar from the registry

```php
public static function dW\Lit\GrammarRegistry::set(string $scopeName, dW\Lit\Grammar $grammar): bool
```

#### Parameters ####

***scopeName*** - The scope name (eg: text.html.php) of the grammar that is being set  
***grammar*** - The grammar to be put into the registry

#### Return Values ####

Returns `true` on success and `false` on failure.


### dW\\Lit\\Highlight::toElement ###

Highlights incoming string data and outputs a PHP `DOMElement`.

```php
public static dW\Lit\Highlight::toElement(string $data, string $scopeName, ?\DOMDocument $document = null, string $encoding = 'windows-1252'): \DOMElement
```

#### Parameters ####

***data*** - The input data string  
***scopeName*** - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data  
***document*** - An existing `DOMDocument` to use as the owner document of the returned `DOMElement`; if omitted one will be created instead  
***encoding*** - The encoding of the input data string; only used if a document wasn't provided in the previous parameter, otherwise it uses the encoding of the existing `DOMDocument`; defaults to HTML standard default windows-1252

#### Return Values ####

Returns a `pre` `DOMElement`.


### dW\\Lit\\Highlight::toString ###

Highlights incoming string data and outputs a string containing serialized HTML.

```php
public static dW\Lit\Highlight::toString(string $data, string $scopeName, string $encoding = 'windows-1252'): string
```

#### Parameters ####

***data*** - The input data string  
***scopeName*** - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data  
***encoding*** - The encoding of the input data string; defaults to HTML standard default windows-1252

#### Return Values ####

Returns a string.


## Examples ##

Here's an example of highlighting PHP code:

```php
$code = <<<CODE
<?php
echo "🐵 OOK! 🐵";
?>
CODE;

$element = dW\Lit\Highlight::toElement($code, 'text.html.php', null, 'UTF-8');
$element->setAttribute('class', 'highlighted');

// Use PHP DOM's DOMDocument::saveHTML method to print the highlighted markup
// when finished with manipulating it.
echo $element->ownerDocument->saveHTML($element);
```

This will produce:

```html
<pre class="highlighted"><code class="text html php"><span class="meta embedded block php"><span class="punctuation section embedded begin php">&lt;?php</span><span class="source php">
<span class="support function construct output php">echo</span> <span class="string quoted double php"><span class="punctuation definition string begin php">"</span>🐵 OOK! 🐵<span class="punctuation definition string end php">"</span></span><span class="punctuation terminator expression php">;</span>
</span><span class="punctuation section embedded end php"><span class="source php">?</span>&gt;</span></span></code></pre>
```

An already existing `DOMDocument` may be used as the owner document of the returned `pre` element:

```php
...
$document = new DOMDocument();
// $element will be owned by $document.
$element = dW\Lit\Highlight::toElement($code, 'text.html.php', $document);
```

Other DOM libraries which inherit from PHP's DOM such as [`MensBeam\HTML`][d] may also be used:

```php
...
$document = new MensBeam\HTML\Document();
// $element will be owned by $document.
$element = dW\Lit\Highlight::toElement($code, 'text.html.php', $document);
// MensBeam\HTML\Element can simply be cast to a string to serialize.
$string = (string)$element;
```

Of course Lit can simply output a string, too:

```php
...
$string = dW\Lit\Highlight::toString($code, 'text.html.php');
```
