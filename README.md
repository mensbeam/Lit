[a]: https://atom.io
[b]: https://github.com/atom/highlights
[c]: https://macromates.com

# Lit #

Lit is a multilanguage syntax highlighter written in PHP. It takes code as input and returns an HTML pre element containing the code highlighted using span elements with classes based upon tokens in the code. It is loosely based upon [Atom][a]'s [Highlights][b] which is used in the Atom text editor to syntax highlight code. Atom's Highlights is in turn based upon [TextMate][c]'s syntax highlighting using its concepts of scope selectors and common keywords for components of programming languages. Lit is not a port of Atom's Highlights but instead an independent implementation of what I can understand of TextMate's grammar syntax, parsing, and tokenization by analyzing other implementations. It aims to at least have feature parity or better with Atom's Highlights.


## Documentation ##

### dW\\Lit\\Highlight::toElement ###

Highlights incoming string data and outputs a PHP `\DOMElement`.

```php
public static dW\Lit\Highlight::toElement(string $data, string $scopeName, ?\DOMDocument $document = null, string $encoding = 'windows-1252'): \DOMElement
```

#### Parameters ####

***data*** - The input data string.  
***scopeName*** - The scope name (eg: text.html.php) of the grammar that's needed to highlight the input data.  
***document*** - An existing `\DOMDocument` to use as the owner document of the returned `\DOMElement`; if omitted one will be created instead.  
***encoding*** - If a document isn't provided an encoding may be provided for the new document; the HTML standard default windows-1252 is used if no encoding is provided.  

#### Return Values ####

Returns a `pre` `\DOMElement`.


## Usage ##

Here's an example of highlighting PHP code:

```php
$code = <<<CODE
<?php
echo "OOK!";
?>
CODE;

$element = dW\Lit\Highlight::toElement($code, 'text.html.php');
// Use PHP DOM's DOMDocument::saveHTML method to print the highlighted markup.
$string = $element->ownerDocument->saveHTML($element);
```
