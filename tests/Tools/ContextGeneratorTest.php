<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Tools;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokenType;
use PhpMyAdmin\SqlParser\Tools\ContextGenerator;

use function file_get_contents;
use function getcwd;

class ContextGeneratorTest extends TestCase
{
    public function testFormatName(): void
    {
        $name = ContextGenerator::formatName('MySql80000');
        $this->assertEquals('MySQL 8.0', $name);

        $name = ContextGenerator::formatName('MariaDb100200');
        $this->assertEquals('MariaDB 10.2', $name);

        $name = ContextGenerator::formatName('MariaDb100000');
        $this->assertEquals('MariaDB 10.0', $name);
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
            TokenType::Keyword->value | Token::FLAG_KEYWORD_RESERVED => [
                'RESERVED',
                'RESERVED2',
                'RESERVED3',
                'RESERVED4',
                'RESERVED5',
            ],
            TokenType::Keyword->value | Token::FLAG_KEYWORD_FUNCTION => ['FUNCTION'],
            TokenType::Keyword->value | Token::FLAG_KEYWORD_DATA_TYPE => ['DATATYPE'],
            TokenType::Keyword->value | Token::FLAG_KEYWORD_KEY => ['KEYWORD'],
            TokenType::Keyword->value => ['NO_FLAG'],
            TokenType::Keyword->value | Token::FLAG_KEYWORD_RESERVED | 4 => ['COMPOSED KEYWORD'],
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
