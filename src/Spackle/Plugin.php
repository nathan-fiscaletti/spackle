<?php

namespace Spackle;

use Spackle\Plugins\CodeBlockParser;

abstract class Plugin
{
    /**
     * The current parser for this Plugin.
     *
     * @var \Spackle\TemplateParser
     */
    protected $parser;

    /**
     * The key used to notate the beginning of this element.
     *
     * @var string
     */
    public $key = '';

    /**
     * Parse the data the the element found matching
     * this parser.
     *
     * @param string $data
     *
     * @return string
     */
    abstract public function parse($data);

    /**
     * Set the parser for the Plugin.
     *
     * @param \Spackle\TemplateParser $parser
     */
    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    /**
     * The current plugins loaded for Spackle.
     *
     * @var array[\Spackle\Plugin]
     */
    private static $plugins = [];

    /**
     * Add a plugin.
     *
     * @param \Spackle\Plugin $plugin
     */
    public static function add($plugin)
    {
        if (! ($plugin instanceof \Spackle\Plugin)) {
            throw new \Exception(
                'Attempting to add a plugin to Spackle '.
                'that does not extend \Spackle\Plugin.'
            );
        }

        foreach (self::plugins() as $plugin_existing) {
            if (
                $plugin->key == $plugin_existing->key
            ) {
                throw new \Exception(
                    'Attempting to add a plugin to Spackle '.
                    'with key that\'s already defined.'
                );
            }
        }

        self::$plugins[] = $plugin;
    }

    /**
     * Retrieve the current plugins.
     *
     * @return array[\Spackle\Plugin]
     */
    public static function plugins()
    {
        return array_merge(self::$plugins, [
            new CodeBlockParser(),
        ]);
    }

    /**
     * Retrieve all start/end keys for the plugins,
     * formatted for usage in regex.
     *
     * @return string
     */
    public static function getIgnoredKeys()
    {
        $ignored = '';
        foreach (self::plugins() as $plugin) {
            $ignored .= (($ignored == '') ? '' : '|').$plugin->key;
        }

        return $ignored;
    }
}
