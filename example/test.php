<?php

include __DIR__.'/../vendor/autoload.php';

use Spackle\FileParser;

// The FileParser extends the TemplateParser
// and instead of cunstructing with a string
// is uses a file path.
$parser = new FileParser(
    // Template File Path
    __DIR__.'/test.spackle',

    // Base Substitutions (add more with $parser->setSubstitution($key, $val))
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
$bindTest = (object) [
    'bound' => 'Yes, Bound.',
];
$parser->bindTo($bindTest);

// You can use functions for substitutions.
// When the return value is an integer or
// a string, it will be directly substituted.
// Otherwise, the value will be used.
$parser->setSubstitution('likes', function () {
    return ['Coding', 'PHP', 'Trial by Combat'];
})

// Add a custom plugin to parse URLs.
->addPlugin(
    new class extends \Spackle\Plugin {
        public $start_key = 'url';
        public $end_key = 'url';

        public function parse($data)
        {
            return 'https://localhost/'.$data;
        }
    }
);

// The ->parse() function will parse the template
// and return the parsed value.
echo $parser->parse();
