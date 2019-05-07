<?php

namespace Spackle;

/**
 * Regex Patterns for Parsing (Parsed in this order):
    Substitution: /(?!>|url)(?<={{)(.*)(?=}})(?!<|url)/Us (Group 1)
    Code Block: /(?<={{)(>)(.*).(<)(?=}})/Us              (Group 2)
 */
class TemplateParser
{   
    /**
     * The content that is being or is about to be
     * parsed, as well as the content that will
     * be returned after parsing.
     * 
     * @var string
     */
    private $content;

    /**
     * These are the currently active substitutions
     * for this TemplateParser.
     * 
     * @var array
     */
    private $substitutions;

    /**
     * The substitution definitons are used
     * when accessing a substitution within
     * a code block.
     * 
     * @var array
     */
    private $substitution_definitions = [];

    /**
     * The currently active parser.
     * 
     * This is nessecary in order to access it from
     * evald code in code blocks.
     * 
     * @var \Spackle\TemplateParser
     */
    public static $current_parser;

    /**
     * The object that this Parser is bound to.
     * 
     * @var mixed
     */
    public $currently_bound_object;

    /**
     * Construct the TemplateParser.
     * 
     * @param string $content       The content to parse.
     * @param array  $substitutions Option substitutions.
     */
    public function __construct($content, $substitutions = [])
    {
        $this->content = $content;
        $this->substitutions = $substitutions;
    }

    /**
     * Set a substitution for the parser.
     * 
     * @param string $substitution The substitution key.
     * @param mixed  $value        The value to apply.
     */
    public function setSubstitution($substitution, $value)
    {
        $this->substitutions[$substitution] = $value;
    }

    /**
     * Bind this parser to a specific object.
     * 
     * @param object &$object
     */
    public function bindTo(&$object)
    {
        $this->currently_bound_object = $object;
    }

    /**
     * Parse the Template.
     * 
     * @return string
     */
    public function parse()
    {
        self::$current_parser = $this;
        $this->parseSubstitutions();
        $this->parseCodeBlocks();
        
        return $this->content;
    }

    /**
     * Parse all Substitutions.
     */
    private function parseSubstitutions()
    {
        $output = $this->content;
        $matches = [];
        preg_match_all('/(?!>|url)(?<={{)(.*)(?=}})(?!<|url)/Us', $output, $matches);
        
        foreach ($matches[0] as $substitution)
        {
            if (strpos($output, '{{'.$substitution.'}}') !== false) {
                $result = $this->substitutions[$substitution];
                if ($result instanceof \Closure) {
                    $callback = $this->substitutions[$substitution];
                    $callback = $callback->bindTo($this);
                    $result = $callback();
                } 

                if (is_string($result) || is_numeric($result)) {
                    $output = str_replace(
                        '{{'.$substitution.'}}',
                        $result,
                        $output
                    );

                    continue;
                }

                // For substitute definitions we place the substition
                // value into a storage array and then when we need
                // it in the code block, instead call it using the
                // __get function.
                $subdefid = count($this->substitution_definitions);
                $this->substitution_definitions[$subdefid] = $result;
                $output = str_replace(
                    '{{'.$substitution.'}}',
                    '\Spackle\TemplateParser::$current_parser->___subdef_'.$subdefid,
                    $output
                );
            }
        }

        $this->content = $output;
    }

    /**
     * Parse all Code Blocks.
     */
    private function parseCodeBlocks()
    {
        $output = $this->content;
        $matches = [];
        preg_match_all('/(?<={{)(>)(.*).(<)(?=}})/Us', $output, $matches);

        foreach ($matches[2] as $match_id => $code) {
            ob_start();
            $bound = str_replace('$this', '\Spackle\TemplateParser::$current_parser', $code);
            if (! is_null($this->currently_bound_object)) {
                $bound = str_replace('$this', '\Spackle\TemplateParser::$current_parser->currently_bound_object', $code);
            }
            @eval($bound);
            $result = ob_get_contents();
            ob_end_clean();

            $output = str_replace(
                '{{'.$matches[0][$match_id].'}}',
                $result,
                $output
            );
        }

        $this->content = $output;
    }

    /**
     * Used to retrieve substitution definitions for
     * substitutions that are within codeblocks.
     * 
     * @param string $parameter
     * 
     * @return mixed
     */
    public function __get($parameter)
    {
        if (strpos($parameter, '___subdef_') === 0) {
            return $this->substitution_definitions[intval(explode('_', $parameter)[1])];
        }

        parent::__get($parameter);
    }
}