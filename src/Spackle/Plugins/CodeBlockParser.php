<?php

namespace Spackle\Plugins;

use Spackle\Plugin;

class CodeBlockParser extends Plugin
{
    /**
     * The current parser for this CodeBlockParser.
     * Used during the eval of the code.
     *
     * @var \Spackle\TemplateParser
     */
    public static $current_parser;

    /**
     * The key used to notate the beginning of this element.
     *
     * @var string
     */
    public $start_key = '>';

    /**
     * The key used to notate the end of this element.
     *
     * @var string
     */
    public $end_key = '<';

    /**
     * Parse the data the the element found matching
     * this parser.
     *
     * @param string $data
     *
     * @return string
     */
    public function parse($data)
    {
        self::$current_parser = $this->parser;

        ob_start();
        $bound = str_replace('$this', '\Spackle\Plugins\CodeBlockParser::$current_parser', $data);
        if (
            ! is_null(
                self::$current_parser->currently_bound_object
            )
        ) {
            $bound = str_replace(
                '$this',
                '\Spackle\Plugins\CodeBlockParser::$current_parser->currently_bound_object',
                $data
            );
        }
        @eval($bound);
        $result = ob_get_contents();
        ob_end_clean();

        self::$current_parser = null;

        return $result;
    }
}
