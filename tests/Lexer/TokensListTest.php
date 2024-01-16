<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\TokenType;

use function count;

class TokensListTest extends TestCase
{
    /**
     * ArrayObj of tokens that are used for testing.
     *
     * @var Token[]
     */
    public array $tokens;

    /**
     * Test setup.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->tokens = [
            new Token('SELECT', TokenType::Keyword),
            new Token(' ', TokenType::Whitespace),
            new Token('*', TokenType::Operator),
            new Token(' ', TokenType::Whitespace),
            new Token('FROM', TokenType::Keyword, Token::FLAG_KEYWORD_RESERVED),
            new Token(' ', TokenType::Whitespace),
            new Token('`test`', TokenType::Symbol),
            new Token(' ', TokenType::Whitespace),
            new Token('WHERE', TokenType::Keyword, Token::FLAG_KEYWORD_RESERVED),
            new Token(' ', TokenType::Whitespace),
            new Token('name', TokenType::None),
            new Token('=', TokenType::Operator),
            new Token('fa', TokenType::None),
        ];
    }

    public function testBuild(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals('SELECT * FROM `test` WHERE name=fa', $list->build());
        $this->assertEquals('SELECT * FROM `test` WHERE name=fa', TokensList::buildFromArray($this->tokens));
    }

    public function testAdd(): void
    {
        $list = new TokensList();
        foreach ($this->tokens as $token) {
            $list->add($token);
        }

        $this->assertEquals(new TokensList($this->tokens), $list);
    }

    public function testGetNext(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[0], $list->getNext());
        $this->assertEquals($this->tokens[2], $list->getNext());
        $this->assertEquals($this->tokens[4], $list->getNext());
        $this->assertEquals($this->tokens[6], $list->getNext());
        $this->assertEquals($this->tokens[8], $list->getNext());
        $this->assertEquals($this->tokens[10], $list->getNext());
        $this->assertEquals($this->tokens[11], $list->getNext());
        $this->assertEquals($this->tokens[12], $list->getNext());
        $this->assertNull($list->getNext());
    }

    public function testGetPrevious(): void
    {
        $list = new TokensList($this->tokens);
        $list->idx = 7;
        $this->assertEquals($this->tokens[6], $list->getPrevious());
        $this->assertEquals($this->tokens[4], $list->getPrevious());
        $this->assertEquals($this->tokens[2], $list->getPrevious());
        $this->assertEquals($this->tokens[0], $list->getPrevious());
        $this->assertNull($list->getPrevious());
    }

    public function testGetNextOfType(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[0], $list->getNextOfType(TokenType::Keyword));
        $this->assertEquals($this->tokens[4], $list->getNextOfType([TokenType::Keyword]));
        $this->assertEquals($this->tokens[6], $list->getNextOfType([TokenType::Keyword, TokenType::Symbol]));
        $this->assertEquals($this->tokens[8], $list->getNextOfType([TokenType::Keyword, TokenType::Symbol]));
        $this->assertNull($list->getNextOfType(TokenType::Keyword));
    }

    public function testGetPreviousOfType(): void
    {
        $list = new TokensList($this->tokens);
        $list->idx = 9;
        $this->assertEquals($this->tokens[8], $list->getPreviousOfType([TokenType::Keyword, TokenType::Symbol]));
        $this->assertEquals($this->tokens[6], $list->getPreviousOfType([TokenType::Keyword, TokenType::Symbol]));
        $this->assertEquals($this->tokens[4], $list->getPreviousOfType([TokenType::Keyword]));
        $this->assertEquals($this->tokens[0], $list->getPreviousOfType(TokenType::Keyword));
        $this->assertNull($list->getPreviousOfType(TokenType::Keyword));
    }

    public function testGetNextOfTypeAndFlag(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[4], $list->getNextOfTypeAndFlag(
            TokenType::Keyword,
            Token::FLAG_KEYWORD_RESERVED,
        ));
        $this->assertEquals($this->tokens[8], $list->getNextOfTypeAndFlag(
            TokenType::Keyword,
            Token::FLAG_KEYWORD_RESERVED,
        ));
        $this->assertNull($list->getNextOfTypeAndFlag(TokenType::Keyword, Token::FLAG_KEYWORD_RESERVED));
    }

    public function testGetNextOfTypeAndValue(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[0], $list->getNextOfTypeAndValue(TokenType::Keyword, 'SELECT'));
        $this->assertNull($list->getNextOfTypeAndValue(TokenType::Keyword, 'SELECT'));
    }

    public function testArrayAccess(): void
    {
        $list = new TokensList();

        // offsetSet(NULL, $value)
        foreach ($this->tokens as $token) {
            $list[] = $token;
        }

        // offsetSet($offset, $value)
        $list[2] = $this->tokens[2];

        // offsetGet($offset)
        for ($i = 0, $count = count($this->tokens); $i < $count; ++$i) {
            $this->assertEquals($this->tokens[$i], $list[$i]);
        }

        // offsetExists($offset)
        $this->assertArrayHasKey(2, $list);
        $this->assertArrayNotHasKey(13, $list);

        // offsetUnset($offset)
        unset($list[2]);
        $this->assertEquals($this->tokens[3], $list[2]);
    }
}
