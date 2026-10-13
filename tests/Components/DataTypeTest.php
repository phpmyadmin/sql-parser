<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\DataType;
use PhpMyAdmin\SqlParser\Exceptions\ParserException;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DataTypeTest extends TestCase
{
    private const LENGTH_ERROR = 'VARCHAR length must be a single nonnegative integer.';

    /**
     * @dataProvider validLengthProvider
     */
    #[DataProvider('validLengthProvider')]
    public function testValidLength(string $declaration, string $length): void
    {
        $parser = new Parser();
        $type = DataType::parse($parser, $this->getTokensList($declaration . ' COLLATE utf8mb4_bin NULL'));
        self::assertSame([], $parser->errors);
        self::assertNotNull($type);
        self::assertSame('VARCHAR', $type->name);
        self::assertSame([$length], $type->parameters);
        self::assertSame('utf8mb4_bin', $type->options->has('COLLATE', true));

        $parser = new Parser('CREATE TABLE temp_users (phone ' . $declaration . ' COLLATE utf8mb4_bin NULL);');
        self::assertSame([], $parser->errors);
        self::assertInstanceOf(CreateStatement::class, $parser->statements[0]);
        self::assertIsArray($parser->statements[0]->fields);
        $field = $parser->statements[0]->fields[0];
        self::assertNotNull($field->type);
        self::assertSame([$length], $field->type->parameters);
        self::assertNotNull($field->options);
        self::assertTrue($field->options->has('NULL'));

        $parser = new Parser('ALTER TABLE temp_users ADD COLUMN phone ' . $declaration . ' NULL;');
        self::assertSame([], $parser->errors);
        self::assertInstanceOf(AlterStatement::class, $parser->statements[0]);
        self::assertStringContainsString($length, $parser->statements[0]->build());
        self::assertStringContainsString('NULL', $parser->statements[0]->build());
    }

    /** @return array<string, array{string, string}> */
    public static function validLengthProvider(): array
    {
        return [
            'ordinary' => ['VARCHAR(20)', '20'],
            'zero' => ['VARCHAR(0)', '0'],
            'leading zeros' => ['VARCHAR(020)', '20'],
            'mixed case and whitespace' => ["vArChAr \n ( 20\t )", '20'],
            'comments' => ['varchar /* type */ ( /* before */ 20 /* after */ )', '20'],
            'line comments' => ["varchar( -- before\n 20 # after\n )", '20'],
        ];
    }

    /**
     * @dataProvider invalidLengthProvider
     */
    #[DataProvider('invalidLengthProvider')]
    public function testInvalidLength(string $length, string $invalidToken): void
    {
        $parser = new Parser();
        DataType::parse($parser, $this->getTokensList('VARCHAR(' . $length . ') NULL'));
        self::assertCount(1, $parser->errors);
        self::assertSame(self::LENGTH_ERROR, $parser->errors[0]->getMessage());
        self::assertInstanceOf(ParserException::class, $parser->errors[0]);
        self::assertNotNull($parser->errors[0]->token);
        self::assertSame($invalidToken, $parser->errors[0]->token->token);

        foreach (self::columnQueries('VARCHAR(' . $length . ')') as $query) {
            $parser = new Parser($query . ' SELECT 1;');
            self::assertCount(1, $parser->errors);
            self::assertSame(self::LENGTH_ERROR, $parser->errors[0]->getMessage());
            self::assertInstanceOf(ParserException::class, $parser->errors[0]);
            self::assertNotNull($parser->errors[0]->token);
            self::assertSame($invalidToken, $parser->errors[0]->token->token);
            self::assertCount(2, $parser->statements);
            self::assertInstanceOf(SelectStatement::class, $parser->statements[1]);

            try {
                new Parser($query, true);
                self::fail('Strict mode must reject an invalid VARCHAR length.');
            } catch (ParserException $exception) {
                self::assertSame(self::LENGTH_ERROR, $exception->getMessage());
            }
        }
    }

    /** @return array<string, array{string, string}> */
    public static function invalidLengthProvider(): array
    {
        return [
            'identifier' => ['X', 'X'],
            'quoted string' => ["'20'", "'20'"],
            'double quoted string' => ['"20"', '"20"'],
            'quoted identifier' => ['`20`', '`20`'],
            'fraction' => ['20.0', '20.0'],
            'negative' => ['-20', '-20'],
            'positive sign' => ['+20', '+20'],
            'scientific' => ['2e1', '2e1'],
            'hexadecimal' => ['0x14', '0x14'],
            'binary' => ['0b10100', '0b10100'],
            'expression' => ['1+19', '+19'],
            'nested parentheses' => ['(20)', '('],
            'empty' => ['', ')'],
            'only comments' => [' /* empty */ ', ')'],
            'extra parameter' => ['20,30', ','],
            'trailing comma' => ['20,', ','],
            'separate numbers' => ['20 /* gap */ 30', '30'],
        ];
    }

    /** @return list<string> */
    private static function columnQueries(string $declaration): array
    {
        return [
            'CREATE TABLE temp_users (phone ' . $declaration . ' NULL);',
            'ALTER TABLE temp_users ADD COLUMN phone ' . $declaration . ' NULL;',
            'ALTER TABLE temp_users ADD phone ' . $declaration . ' NULL;',
        ];
    }

    /**
     * @param list<string> $parameters
     *
     * @dataProvider otherTypeProvider
     */
    #[DataProvider('otherTypeProvider')]
    public function testOtherTypes(string $declaration, array $parameters): void
    {
        $parser = new Parser();
        $type = DataType::parse($parser, $this->getTokensList($declaration));
        self::assertSame([], $parser->errors);
        self::assertNotNull($type);
        self::assertSame($parameters, $type->parameters);
    }

    /** @return array<string, array{string, list<string>}> */
    public static function otherTypeProvider(): array
    {
        return [
            'enum' => ["ENUM('a','b')", ["'a'", "'b'"]],
            'set' => ["SET('a','b')", ["'a'", "'b'"]],
            'decimal' => ['DECIMAL(10,2)', ['10', '2']],
            'lengthless varchar' => ['VARCHAR', []],
        ];
    }

    public function testLengthlessRoutine(): void
    {
        $parser = new Parser('CREATE PROCEDURE p(IN phone VARCHAR) SELECT phone;');
        self::assertSame([], $parser->errors);
    }

    public function testAlterContinuation(): void
    {
        $parser = new Parser(
            'ALTER TABLE temp_users ADD COLUMN phone VARCHAR(X) NULL, ADD COLUMN name VARCHAR(20) NOT NULL;'
        );
        self::assertCount(1, $parser->errors);
        self::assertSame(self::LENGTH_ERROR, $parser->errors[0]->getMessage());
        self::assertInstanceOf(AlterStatement::class, $parser->statements[0]);
        self::assertNotNull($parser->statements[0]->altered);
        self::assertCount(2, $parser->statements[0]->altered);
        self::assertStringContainsString('VARCHAR(20) NOT NULL', $parser->statements[0]->build());
    }

    public function testAlterStringParameters(): void
    {
        $parser = new Parser("ALTER TABLE temp_users ADD COLUMN e ENUM('a','b'), ADD COLUMN s SET('a','b');");
        self::assertSame([], $parser->errors);
        self::assertInstanceOf(AlterStatement::class, $parser->statements[0]);
        self::assertNotNull($parser->statements[0]->altered);
        self::assertCount(2, $parser->statements[0]->altered);
        self::assertStringContainsString("ENUM('a','b')", $parser->statements[0]->build());
    }
}
