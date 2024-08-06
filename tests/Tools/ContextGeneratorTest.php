<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Tools;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\Tools\ContextGenerator;

use function file_get_contents;
use function getcwd;

class ContextGeneratorTest extends TestCase
{
    public function testFormatName(): void
    {
        $name = ContextGenerator::formatName('Invalid00Format00');
        $this->assertEquals('Invalid00Format00', $name);

        $name = ContextGenerator::formatName('MySql80000');
        $this->assertEquals('MySQL 8.0', $name);

        $name = ContextGenerator::formatName('MariaDb100200');
        $this->assertEquals('MariaDB 10.2', $name);

        $name = ContextGenerator::formatName('MariaDb100000');
        $this->assertEquals('MariaDB 10.0', $name);

        $name = ContextGenerator::formatName('FutureDBMS45784012500');
        $this->assertEquals('FutureDBMS 4.57.84.1.25', $name);
    }

    public function testSortWords(): void
    {
        $wordsArray = ['41' => ['GEOMETRYCOLLECTION', 'DATE'], '35' => ['SCHEMA', 'REPEAT', 'VALUES']];
        ContextGenerator::sortWords($wordsArray);
        $this->assertEquals([
            '41' => ['DATE', 'GEOMETRYCOLLECTION'],
            '35' => ['REPEAT', 'SCHEMA', 'VALUES'],
        ], $wordsArray);
    }

    public function testReadWords(): void
    {
        $testFiles = [getcwd() . '/tests/Tools/contexts/testContext.txt'];
        $readWords = ContextGenerator::readWords($testFiles);
        $this->assertEquals([
            Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED => [
                'RESERVED',
                'RESERVED2',
                'RESERVED3',
                'RESERVED4',
                'RESERVED5',
            ],
            Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_FUNCTION => ['FUNCTION'],
            Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_DATA_TYPE => ['DATATYPE'],
            Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_KEY => ['KEYWORD'],
            Token::FLAG_KEYWORD => ['NO_FLAG'],
            Token::FLAG_KEYWORD | Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_COMPOSED => ['COMPOSED KEYWORD'],
        ], $readWords);
    }

    public function testGenerate(): void
    {
        $testFiles = [getcwd() . '/tests/Tools/contexts/testContext.txt'];
        $readWords = ContextGenerator::readWords($testFiles);
        ContextGenerator::printWords($readWords);
        $options = [
            'keywords' => $readWords,
            'name' => 'MYSQL TEST',
            'class' => 'TestContext',
            'link' => 'https://www.phpmyadmin.net/contribute',
        ];
        $generatedTemplate = ContextGenerator::generate($options);
        $expectedTemplate = file_get_contents(getcwd() . '/tests/Tools/templates/TestContext.php');
        $this->assertEquals($expectedTemplate, $generatedTemplate);
    }
}
