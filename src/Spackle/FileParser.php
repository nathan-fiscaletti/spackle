<?php

namespace Spackle;

class FileParser extends TemplateParser
{
    /**
     * Construct the FileParser.
     *
     * @param string $file          The path to the Template file.
     * @param array  $substitutions Optional substitutions.
     */
    public function __construct($file, $substitutions = [])
    {
        if (!file_exists($file)) {
            throw new \Exception('Missing template file: '.$file);
        }

        if (!is_readable($file)) {
            throw new \Exception('Template file is not readable: '.$file);
        }

        parent::__construct(file_get_contents($file), $substitutions);
    }
}
