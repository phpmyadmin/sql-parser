<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser;

use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\MoTranslator\Translator as MoTranslator;
use RuntimeException;

use function assert;
use function class_exists;

/**
 * Defines the localization helper infrastructure of the library.
 */
class Translator
{
    /**
     * The MoTranslator loader object.
     */
    private static Loader|null $loader = null;

    /**
     * The MoTranslator translator object.
     */
    private static MoTranslator|null $translator = null;

    private static string $locale = '';

    /**
     * Loads translator.
     *
     * @internal This method is not covered by the backward compatibility promise for SQL-Parser
     */
    public static function load(): void
    {
        if (! class_exists(Loader::class)) {
            throw new RuntimeException('The phpmyadmin/motranslator package is missing.');
        }

        if (! isset(self::$loader)) {
            // Create loader object
            self::$loader = new Loader();

            if (self::$locale === '') {
                self::$locale = self::$loader->detectlocale();
            }

            self::$loader->setlocale(self::$locale);

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
        if (! class_exists(Loader::class)) {
            return $msgid;
        }

        self::load();
        assert(self::$translator instanceof MoTranslator);

        return self::$translator->gettext($msgid);
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }
}
