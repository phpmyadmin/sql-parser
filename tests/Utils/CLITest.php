<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Utils;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Utils\CLI;
use PHPUnit\Framework\Attributes\DataProvider;

use function dirname;
use function exec;

use const PHP_BINARY;

class CLITest extends TestCase
{
    /** @param array<string, bool|string>|false $getopt */
    private function getCLI(array|false $getopt): CLI
    {
        $cli = $this->createPartialMock(CLI::class, ['getopt']);
        $cli->method('getopt')->willReturn($getopt);

        return $cli;
    }

    /** @param array<string, bool|string>|false $getopt */
    private function getCLIStdIn(string $input, array|false $getopt): CLI
    {
        $cli = $this->createPartialMock(CLI::class, ['getopt', 'readStdin']);
        $cli->method('getopt')->willReturn($getopt);
        $cli->method('readStdin')->willReturn($input);

        return $cli;
    }

    /**
     * Test that getopt call works.
     *
     * We do mock it for other tests to return values we want.
     */
    public function testGetopt(): void
    {
        $cli = new CLI();
        $this->assertEquals(
            [],
            $cli->getopt('', []),
        );
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('highlightParamsProvider')]
    public function testRunHighlight(array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runHighlight());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{(array<string, bool|string>|false), string, int}>
     */
    public static function highlightParamsProvider(): array
    {
        return [
            [
                ['q' => 'SELECT 1'],
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m\n",
                0,
            ],
            [
                ['query' => 'SELECT 1'],
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m\n",
                0,
            ],
            [
                [
                    'q' => 'SELECT /* comment */ 1 /* other */',
                    'f' => 'text',
                ],
                "SELECT\n    /* comment */ 1 /* other */\n",
                0,
            ],
            [
                [
                    'q' => 'SELECT 1',
                    'f' => 'foo',
                ],
                "ERROR: Invalid value for format!\n",
                1,
            ],
            [
                [
                    'q' => 'SELECT 1',
                    'f' => 'html',
                ],
                '<span class="sql-reserved">SELECT</span><br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>' . "\n",
                0,
            ],
            [
                ['h' => true],
                'Usage: sql-parser --highlight --query SQL [--format html|cli|text] [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --highlight' . "\n",
                0,
            ],
            [
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --highlight --query SQL [--format html|cli|text] [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --highlight' . "\n",
                1,
            ],
            [
                false,
                '',
                1,
            ],
        ];
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('highlightParamsStdInProvider')]
    public function testRunHighlightStdIn(string $input, array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLIStdIn($input, $getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runHighlight());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{string, (array<string, bool|string>|false), string, int}>
     */
    public static function highlightParamsStdInProvider(): array
    {
        return [
            [
                'SELECT 1',
                [],
                "\x1b[35mSELECT\n    \x1b[92m1\x1b[0m\n",
                0,
            ],
            [
                'SELECT /* comment */ 1 /* other */',
                ['f' => 'text'],
                "SELECT\n    /* comment */ 1 /* other */\n",
                0,
            ],
            [
                'SELECT 1',
                ['f' => 'foo'],
                "ERROR: Invalid value for format!\n",
                1,
            ],
            [
                'SELECT 1',
                ['f' => 'html'],
                '<span class="sql-reserved">SELECT</span><br/>' .
                '&nbsp;&nbsp;&nbsp;&nbsp;<span class="sql-number">1</span>' . "\n",
                0,
            ],
            [
                '',
                ['h' => true],
                'Usage: sql-parser --highlight --query SQL [--format html|cli|text] [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --highlight' . "\n",
                0,
            ],
            [
                '',
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --highlight --query SQL [--format html|cli|text] [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --highlight' . "\n",
                1,
            ],
            [
                '',
                false,
                '',
                1,
            ],
        ];
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('lintParamsStdInProvider')]
    public function testRunLintFromStdIn(string $input, array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLIStdIn($input, $getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runLint());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{string, (array<string, bool|string>|false), string, int}>
     */
    public static function lintParamsStdInProvider(): array
    {
        return [
            [
                'SELECT 1',
                [],
                '',
                0,
            ],
            [
                'SELECT SELECT',
                [],
                '#1: An expression was expected. (near "SELECT" at position 7)' . "\n" .
                '#2: This type of clause was previously parsed. (near "SELECT" at position 7)' . "\n" .
                '#3: An expression was expected. (near "" at position 0)' . "\n",
                10,
            ],
            [
                'SELECT SELECT',
                ['c' => 'MySql80000'],
                '#1: An expression was expected. (near "SELECT" at position 7)' . "\n" .
                '#2: This type of clause was previously parsed. (near "SELECT" at position 7)' . "\n" .
                '#3: An expression was expected. (near "" at position 0)' . "\n",
                10,
            ],
            [
                '',
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --lint --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --lint' . "\n",
                1,
            ],
            [
                '',
                ['h' => true],
                'Usage: sql-parser --lint --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --lint' . "\n",
                0,
            ],
            [
                '',
                false,
                '',
                1,
            ],
        ];
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('lintParamsProvider')]
    public function testRunLint(array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runLint());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{(array<string, bool|string>|false), string, int}>
     */
    public static function lintParamsProvider(): array
    {
        return [
            [
                ['q' => 'SELECT 1'],
                '',
                0,
            ],
            [
                ['query' => 'SELECT 1'],
                '',
                0,
            ],
            [
                ['q' => 'SELECT SELECT'],
                '#1: An expression was expected. (near "SELECT" at position 7)' . "\n" .
                '#2: This type of clause was previously parsed. (near "SELECT" at position 7)' . "\n" .
                '#3: An expression was expected. (near "" at position 0)' . "\n",
                10,
            ],
            [
                [
                    'q' => 'SELECT SELECT',
                    'c' => 'MySql80000',
                ],
                '#1: An expression was expected. (near "SELECT" at position 7)' . "\n" .
                '#2: This type of clause was previously parsed. (near "SELECT" at position 7)' . "\n" .
                '#3: An expression was expected. (near "" at position 0)' . "\n",
                10,
            ],
            [
                ['h' => true],
                'Usage: sql-parser --lint --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --lint' . "\n",
                0,
            ],
            [
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --lint --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --lint' . "\n",
                1,
            ],
            [
                false,
                '',
                1,
            ],
        ];
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('tokenizeParamsProvider')]
    public function testRunTokenize(array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLI($getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runTokenize());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{(array<string, bool|string>|false), string, int}>
     */
    public static function tokenizeParamsProvider(): array
    {
        $result = "[TOKEN 0]\nType = 1\nFlags = 3\nValue = 'SELECT'\nToken = 'SELECT'\n\n"
            . "[TOKEN 1]\nType = 3\nFlags = 0\nValue = ' '\nToken = ' '\n\n"
            . "[TOKEN 2]\nType = 6\nFlags = 0\nValue = 1\nToken = '1'\n\n"
            . "[TOKEN 3]\nType = 9\nFlags = 0\nValue = ''\nToken = ''\n\n";

        return [
            [
                ['q' => 'SELECT 1'],
                $result,
                0,
            ],
            [
                ['query' => 'SELECT 1'],
                $result,
                0,
            ],
            [
                ['h' => true],
                'Usage: sql-parser --tokenize --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --tokenize' . "\n",
                0,
            ],
            [
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --tokenize --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --tokenize' . "\n",
                1,
            ],
            [
                false,
                '',
                1,
            ],
        ];
    }

    /** @param array<string, bool|string>|false $getopt */
    #[DataProvider('tokenizeParamsStdInProvider')]
    public function testRunTokenizeStdIn(string $input, array|false $getopt, string $output, int $result): void
    {
        $cli = $this->getCLIStdIn($input, $getopt);
        $this->expectOutputString($output);
        $this->assertEquals($result, $cli->runTokenize());
    }

    /**
     * @return array<int, array<int, int|string|array<string, bool|string>|false>>
     * @psalm-return list<array{string, (array<string, bool|string>|false), string, int}>
     */
    public static function tokenizeParamsStdInProvider(): array
    {
        $result = "[TOKEN 0]\nType = 1\nFlags = 3\nValue = 'SELECT'\nToken = 'SELECT'\n\n"
            . "[TOKEN 1]\nType = 3\nFlags = 0\nValue = ' '\nToken = ' '\n\n"
            . "[TOKEN 2]\nType = 6\nFlags = 0\nValue = 1\nToken = '1'\n\n"
            . "[TOKEN 3]\nType = 9\nFlags = 0\nValue = ''\nToken = ''\n\n";

        return [
            [
                'SELECT 1',
                [],
                $result,
                0,
            ],
            [
                '',
                ['h' => true],
                'Usage: sql-parser --tokenize --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --tokenize' . "\n",
                0,
            ],
            [
                '',
                [],
                'ERROR: Missing parameters!' . "\n" .
                'Usage: sql-parser --tokenize --query SQL [--ansi]' . "\n" .
                '       cat file.sql | sql-parser --tokenize' . "\n",
                1,
            ],
            [
                '',
                false,
                '',
                1,
            ],
        ];
    }

    #[DataProvider('stdinParamsProvider')]
    public function testStdinPipe(string $cmd, int $result): void
    {
        exec($cmd, $out, $ret);
        $this->assertSame($result, $ret);
    }

    /**
     * @return array<int, array<int, int|string>>
     * @psalm-return list<array{string, int}>
     */
    public static function stdinParamsProvider(): array
    {
        $binPath = PHP_BINARY . ' ' . dirname(__DIR__, 2) . '/bin/';

        return [
            [
                'echo "SELECT 1" | ' . $binPath . 'sql-parser --highlight',
                0,
            ],
            [
                'echo "invalid query" | ' . $binPath . 'sql-parser --highlight',
                0,
            ],
            [
                'echo "SELECT 1" | ' . $binPath . 'sql-parser --lint',
                0,
            ],
            [
                'echo "invalid query" | ' . $binPath . 'sql-parser --lint',
                10,
            ],
            [
                'echo "SELECT 1" | ' . $binPath . 'sql-parser --tokenize',
                0,
            ],
            [
                'echo "invalid query" | ' . $binPath . 'sql-parser --tokenize',
                0,
            ],
        ];
    }
}
