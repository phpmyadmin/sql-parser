<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\MoTranslator\Translator as MoTranslator;

use function class_exists;

/**
 * Defines the localization helper infrastructure of the library.
 */
class Translator
{
    /**
     * The MoTranslator loader object.
     */
    private static Loader $loader;

    /**
     * The MoTranslator translator object.
     */
    private static MoTranslator $translator;

    /**
     * Loads translator.
     */
    public static function load(): void
    {
        if (! isset(self::$loader)) {
            // Create loader object
            self::$loader = new Loader();

            // Set locale
            self::$loader->setlocale(
                self::$loader->detectlocale(),
            );

            // Set default text domain
            self::$loader->textdomain('sqlparser');

            // Set path where to look for a domain
            self::$loader->bindtextdomain('sqlparser', __DIR__ . '/../locale/');
        }

        if (isset(self::$translator)) {
            return;
        }

        // Get translator
        self::$translator = self::$loader->getTranslator();
    }

    /**
     * Translates a string.
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    public static function gettext(string $msgid): string
    {
        if (! class_exists(Loader::class, true)) {
            return $msgid;
        }

        self::load();

        return self::$translator->gettext($msgid);
    }
}
