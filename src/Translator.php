<?php

/**
 * Defines the localization helper infrastructure of the library.
 *
 * @package SqlParser
 */
namespace SqlParser;

use MoTranslator;

class Translator
{
    /**
     * Translator instance
     *
     * @access private
     * @static
     * @var Translator
     */
    private static $_instance;

    /**
     * The MoTranslator loader object.
     *
     * @var MoTranslator\Loader
     */
    private $loader;

    /**
     * The MoTranslator translator object.
     *
     * @var MoTranslator\Translator
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Create loader object
        $this->loader = new MoTranslator\Loader();

        // Set locale
        $this->loader->setlocale(
            $this->loader->detectlocale()
        );

        // Set default text domain
        $this->loader->textdomain('sqlparser');

        // Set path where to look for a domain
        $this->loader->bindtextdomain('sqlparser', __DIR__ . '/../locale/');

        // Get translator
        $this->translator = $this->loader->get_translator();
    }

    /**
     * Translates a string
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    public function gettext($msgid)
    {
        return $this->translator->gettext($msgid);
    }

    /**
     * Returns the singleton Translator object
     *
     * @return Translator object
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new Translator();
        }
        return self::$_instance;
    }
}
