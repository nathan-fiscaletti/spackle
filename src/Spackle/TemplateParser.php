<?php

namespace Spackle;

/**
 * Regex Patterns for Parsing (Parsed in this order):
 * Substitution: /(?!>|url)(?<={{)(.*)(?=}})(?!<|url)/Us (Group 1)
 * Code Block: /(?<={{)(>)(.*).(<)(?=}})/Us              (Group 2).
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
     * List of plugins for this parser.
     *
     * @var array[\Spackle\Plugin]
     */
    private $plugins = [];

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
     *
     * @return \Spackle\TemplateParser
     */
    public function setSubstitution($substitution, $value)
    {
        $this->substitutions[$substitution] = $value;

        return $this;
    }

    /**
     * Bind this parser to a specific object.
     *
     * @param object &$object
     *
     * @return \Spackle\TemplateParser
     */
    public function bindTo(&$object)
    {
        $this->currently_bound_object = $object;

        return $this;
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

        foreach ($this->plugins() as $plugin) {
            $plugin->setParser($this);
            $output = $this->content;
            $matches = [];
            preg_match_all('/(?<={{)('.$plugin->key.')(.*).(<)(?=}})/Us', $output, $matches);
            foreach ($matches[2] as $match_id => $data) {
                $result = $plugin->parse(trim($data));

                if (! (is_string($result) || is_numeric($result) || is_array($result))) {
                    if (is_bool($result)) {
                        $result = ($result) ? 'true' : 'false';
                    } else if (empty($result)) {
                        $result = 'null';
                    }
                }

                $output = str_replace(
                    '{{'.$matches[0][$match_id].'}}',
                    $result,
                    $output
                );
            }

            $this->content = $output;
        }

        return $this->content;
    }

    /**
     * Parse all Substitutions.
     */
    private function parseSubstitutions()
    {
        $output = $this->content;
        $matches = [];
        $ignored = $this->getIgnoredKeys();
        preg_match_all('/(?!'.$ignored.')(?<={{)(.*)(?=}})(?!<)/Us', $output, $matches);

        foreach ($matches[0] as $substitution) {
            if (strpos($output, '{{'.$substitution.'}}') !== false) {
                $result = $this->substitutions[$substitution];
                if ($result instanceof \Closure) {
                    $callback = $this->substitutions[$substitution];
                    $callback = $callback->bindTo(
                        is_null($this->currently_bound_object)
                            ? $this
                            : $this->currently_bound_object
                    );
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
     * Add a plugin.
     *
     * @param \Spackle\Plugin $plugin
     *
     * @return \Spackle\TemplateParser
     */
    public function addPlugin($plugin)
    {
        if (! ($plugin instanceof \Spackle\Plugin)) {
            throw new \Exception(
                'Attempting to add a plugin to Spackle '.
                'that does not extend \Spackle\Plugin.'
            );
        }

        foreach ($this->plugins() as $plugin_existing) {
            if (
                $plugin->key == $plugin_existing->key
            ) {
                throw new \Exception(
                    'Attempting to add a plugin to Spackle '.
                    'with a key that\'s already defined.'
                );
            }
        }

        $this->plugins[] = $plugin;

        return $this;
    }

    /**
     * Retrieve the current plugins.
     *
     * @return array[\Spackle\Plugin]
     */
    private function plugins()
    {
        return array_merge(
            $this->plugins,
            Plugin::plugins()
        );
    }

    /**
     * Retrieve all start/end keys for the plugins,
     * formatted for usage in regex.
     *
     * @return string
     */
    private function getIgnoredKeys()
    {
        $ignored = '';
        foreach ($this->plugins() as $plugin) {
            $ignored .= (($ignored == '') ? '' : '|').$plugin->key;
        }

        return $ignored;
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
