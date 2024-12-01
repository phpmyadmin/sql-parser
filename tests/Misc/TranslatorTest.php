<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Misc;

use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\MoTranslator\Translator as MoTranslator;
use PhpMyAdmin\SqlParser\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function realpath;

#[CoversClass(Translator::class)]
final class TranslatorTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        $loaderProperty = new ReflectionProperty(Translator::class, 'loader');
        $loaderProperty->setValue(null, null);
        $translatorProperty = new ReflectionProperty(Translator::class, 'translator');
        $translatorProperty->setValue(null, null);
        Translator::setLocale('en');
    }

    public function testLocale(): void
    {
        Translator::setLocale('en');
        self::assertSame('en', Translator::getLocale());
        Translator::setLocale('fr');
        self::assertSame('fr', Translator::getLocale());
        Translator::setLocale('');
        self::assertSame('', Translator::getLocale());
    }

    #[TestWith([null, 'en', 'en'])]
    #[TestWith([null, 'fr', 'fr'])]
    #[TestWith(['en', '', 'en'])]
    #[TestWith(['fr', '', 'fr'])]
    public function testLoad(string|null $globalLang, string $locale, string $expectedLocale): void
    {
        $loaderProperty = new ReflectionProperty(Translator::class, 'loader');
        $loaderProperty->setValue(null, null);
        $translatorProperty = new ReflectionProperty(Translator::class, 'translator');
        $translatorProperty->setValue(null, null);
        $GLOBALS['lang'] = $globalLang;
        Translator::setLocale($locale);

        Translator::load();

        self::assertSame($expectedLocale, Translator::getLocale());
        self::assertInstanceOf(MoTranslator::class, $translatorProperty->getValue());
        $loader = $loaderProperty->getValue();
        self::assertInstanceOf(Loader::class, $loader);
        $loaderClass = new ReflectionClass(Loader::class);
        $localeProperty = $loaderClass->getProperty('locale');
        self::assertSame($expectedLocale, $localeProperty->getValue($loader));
        // Compatibility with MoTranslator < 5
        $defaultDomainProperty = $loaderClass->hasProperty('default_domain')
            ? $loaderClass->getProperty('default_domain')
            : $loaderClass->getProperty('defaultDomain');
        self::assertSame('sqlparser', $defaultDomainProperty->getValue($loader));
        $pathsProperty = $loaderClass->getProperty('paths');
        self::assertSame(
            ['' => './', 'sqlparser' => realpath(__DIR__ . '/../../src/') . '/../locale/'],
            $pathsProperty->getValue($loader),
        );
    }

    public function testGettext(): void
    {
        $loaderProperty = new ReflectionProperty(Translator::class, 'loader');
        $loaderProperty->setValue(null, null);
        $translatorProperty = new ReflectionProperty(Translator::class, 'translator');
        $translatorProperty->setValue(null, null);
        Translator::setLocale('pt_BR');
        self::assertSame(
            'Início de declaração inesperado.',
            Translator::gettext('Unexpected beginning of statement.'),
        );

        $loaderProperty = new ReflectionProperty(Translator::class, 'loader');
        $loaderProperty->setValue(null, null);
        $translatorProperty = new ReflectionProperty(Translator::class, 'translator');
        $translatorProperty->setValue(null, null);
        Translator::setLocale('en');
        self::assertSame(
            'Unexpected beginning of statement.',
            Translator::gettext('Unexpected beginning of statement.'),
        );
    }
}
