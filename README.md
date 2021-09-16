[a]: https://atom.io
[b]: https://github.com/atom/highlights
[c]: https://macromates.com
[d]: https://code.mensbeam.com/MensBeam/HTML

# Lit #

Lit is a multilanguage syntax highlighter written in PHP. It takes code as input and returns an HTML pre element containing the code highlighted using span elements with classes based upon tokens in the code. It is loosely based upon [Atom][a]'s [Highlights][b] which is used in the Atom text editor to syntax highlight code. Atom's Highlights is in turn based upon [TextMate][c]'s syntax highlighting using its concepts of scope selectors and common keywords for components of programming languages. Lit is not a port of Atom's Highlights but instead an independent implementation of what I can understand of TextMate's grammar syntax, parsing, and tokenization by analyzing other implementations. It aims to at least have feature parity or better with Atom's Highlights.


## Documentation ##

### dW\\Lit\\Highlight::toElement ###

Highlights incoming string data and outputs a PHP `DOMElement`.

```php
public static dW\Lit\Highlight::toElement(string $data, string $scopeName, ?\DOMDocument $document = null, string $encoding = 'windows-1252'): \DOMElement
```

#### Parameters ####

***data*** - The input data string.  
***scopeName*** - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data.  
***document*** - An existing `DOMDocument` to use as the owner document of the returned `DOMElement`; if omitted one will be created instead.  
***encoding*** - If a document isn't provided an encoding may be provided for the new document; the HTML standard default windows-1252 is used if no encoding is provided.  

#### Return Values ####

Returns a `pre` `DOMElement`.


## Examples ##

Here's an example of simply highlighting PHP code:

```php
$code = <<<CODE
<?php
echo "OOK!";
?>
CODE;

$element = dW\Lit\Highlight::toElement($code, 'text.html.php');
// Use PHP DOM's DOMDocument::saveHTML method to print the highlighted markup.
echo $element->ownerDocument->saveHTML($element);
```

This will produce:

<style>
body {
line-height: 1.6;
font-size: 16px;
}

pre.highlighted {
background-color: #414452;
color: #f5f3f2
}
pre.highlighted .comment, pre.highlighted .comment .punctuation {
color: #898a92
}
pre.highlighted .constant {
color: #ffae71
}
pre.highlighted .constant.character.escape {
color: #b0d5d1
}
pre.highlighted .constant.numeric {
color: #ffae71
}
pre.highlighted .constant.other.color {
color: #b0d5d1
}
pre.highlighted .constant.other.symbol {
color: #ccd479
}
pre.highlighted .entity.name.class,pre.highlighted .entity.name.namespace {
color: #f2c784
}
pre.highlighted .entity.name.function,pre.highlighted .entity.name.section {
color: #88d8fa
}
pre.highlighted .entity.name.tag {
color: #ffb0a2
}
pre.highlighted .entity.name.type.class {
color: #f2c784
}
pre.highlighted .entity.other.attribute-name {
color: #d9c6ea
}
pre.highlighted .entity.other.attribute-name.id {
color: #88d8fa
}
pre.highlighted .entity.other.inherited-class {
color: #ccd479
}
pre.highlighted .invalid.illegal {
color: #414452;
background-color: #ffb0a2
}
pre.highlighted .keyword {
color: #d9c6ea
}
pre.highlighted .keyword.arithmetic {
color: #f5f3f2
}
pre.highlighted .keyword.other.special-method {
color: #88d8fa
}
pre.highlighted .keyword.other.unit {
color: #ffae71
}
pre.highlighted .markup.bold {
font-weight: 700;
color: #f2c784
}
pre.highlighted .markup.changed {
color: #d9c6ea
}
pre.highlighted .markup.deleted {
color: #ffb0a2
}
pre.highlighted .markup.heading {
color: #88d8fa
}
pre.highlighted .markup.inserted {
color: #ccd479
}
pre.highlighted .markup.italic {
font-style: italic;
color: #d9c6ea
}
pre.highlighted .markup.list {
color: #ffb0a2
}
pre.highlighted .markup.quote {
color: #ffae71
}
pre.highlighted .markup.raw.inline {
color: #ccd479
}
pre.highlighted .meta.class {
color: #f5f3f2
}
pre.highlighted .meta.link {
color: #ffae71
}
pre.highlighted .meta.require {
color: #88d8fa
}
pre.highlighted .meta.selector {
color: #d9c6ea
}
pre.highlighted .meta.separator {
color: #f5f3f2;
background-color: #adadb2
}
pre.highlighted .meta .support.type.property-name,pre.highlighted .meta.property-name {
color: #f2c784
}
pre.highlighted .none, pre.highlighted .punctuation {
color: #f5f3f2
}
pre.highlighted .storage {
color: #d9c6ea
}
pre.highlighted .string {
color: #ccd479
}
pre.highlighted .string.other.link {
color: #ffb0a2
}
pre.highlighted .string.regexp {
color: #b0d5d1
}
pre.highlighted .string .variable {
color: #ffbd91
}
pre.highlighted .support.class, pre.highlighted .support.namespace {
color: #f2c784
}
pre.highlighted .support.function,pre.highlighted .support.type {
color: #b0d5d1
}
pre.highlighted .support.function.any-method {
color: #88d8fa
}
pre.highlighted .variable {
color: #ffb0a2
}
pre.highlighted .variable.interpolation {
color: #ffbd91
}
pre.highlighted .variable.parameter.function {
color: #f5f3f2
}
</style>
<pre class="highlighted"><code class="text html php"><span class="meta embedded block php"><span class="punctuation section embedded begin php">&lt;?php</span><span class="source php">
<span class="support function construct output php">echo</span> <span class="string quoted double php"><span class="punctuation definition string begin php">"</span>OOK!<span class="punctuation definition string end php">"</span></span><span class="punctuation terminator expression php">;</span>
</span><span class="punctuation section embedded end php"><span class="source php">?</span>&gt;</span></span></code></pre>

An existing `DOMDocument` may be used as the owner document of the returned `pre` element:

```php
$code = <<<CODE
<?php
echo "OOK!";
?>
CODE;

$document = new DOMDocument();
// $element will be owned by $document.
$element = dW\Lit\Highlight::toElement($code, 'text.html.php', $document);
```

Other DOM libraries which inherit from PHP's DOM such as [`MensBeam\HTML`][d] may also be used:

```php
$code = <<<CODE
<?php
echo "OOK!";
?>
CODE;

$document = new MensBeam\HTML\Document();
// $element will be owned by $document.
$element = dW\Lit\Highlight::toElement($code, 'text.html.php', $document);
// MensBeam\HTML\Element can simply be cast to a string to serialize.
$string = (string)$element;
```
