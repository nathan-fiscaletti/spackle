# Spackle
> A simple templating engine for PHP

Spackle allows you to add code blocks and substitutions to any file.

## Creating Templates

### Substitutions

> Substitution Notation: `{{...}}`

```php
<body>
   Hello, my name is {{name}}.
</body>
```

Substitutions will be replaced based on the configuration of their Parser.

### Code Blocks

> Code Block Notation: `{{> ... <}}`

```php
<body>
  {{>
    echo "Hello, Word!";
  <}}
</body>
```

You can also use Substitutions within code blocks.

```php
<ul>
{{> 
  foreach ({{likes}} as $likeable) {
    echo '<li>';
    echo $likeable;
    echo '</li>';
  }
<}}
</ul>
```

## Parsing Templates

Once you've created a template, you can parse it in PHP. To parse the template you need to create an instance of `\Spackle\TemplateParser`. In the following example, we will use the `\Spackle\FileParser` class. It is a subclass of the `TemplateParser`, the only difference being that it loads the contents of a file in instead of using a string.

For this example, we will use the following Spackle Template stored in `./test.spackle`.
```html
<h4>Welcome to Spackle!</h4>

Spackle was created by <a href="{{github_url}}" target="_blank">{{name}}</a>.<br />

Some things that {{name}} likes are:<br />
<ul>

{{> 
    foreach ({{likes}} as $likeable) {
        echo '<li>';
        echo $likeable;
        echo '</li>';
    }
<}}

</ul>

<b>Bound: {{> echo $this->bound; <}}
```

Now, to parse it we will need to tell it what values to use.

```php
<?php

include_once __DIR__.'/vendor/autoload.php';

use \Spackle\FileParser;

// The FileParser extends the TemplateParser
// and instead of cunstructing with a string
// is uses a file path.
$parser = new FileParser(
    // Template File Path
    __DIR__.'/test.spackle',

    // Optional Substitutions (add more with $parser->setSubstitution($key, $val))
    [
        'github_url' => 'https://github.com/nathan-fiscaletti/',
    ]
);

// You can set substitutions easily using the setSubstitution
// member function of a TemplateParser.
$parser->setSubstitution('name', 'Nathan Fiscaletti');

// You can also bind the Parser to a specific object.
// Anytime you reference `$this` in your code blocks
// within the template, this bound object will be
// referenced.
$bindTest = (object)[
    'bound' => 'Yes, Bound.'
];
$parser->bindTo($bindTest);

// You can use functions for substitutions.
// When the return value is an integer or
// a string, it will be directly substituted.
// Otherwise, the value will be used.
$parser->setSubstitution('likes', function() {
    return ['Coding', 'PHP', 'Trial by Combat'];
});

// The ->parse() function will parse the template
// and return the parsed value.
echo $parser->parse();
```

This should output something like this:
```
Welcome to Spackle!

Spackle was created by Nathan Fiscaletti.

Some things that Nathan Fiscaletti likes are:
    * Coding
    * PHP
    * Trial by Combat

Bound: Yes, Bound.
```

> (You can find this example in [./example/](./example/)
