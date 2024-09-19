<?php

declare(strict_types=1);

namespace PhpMyAdmin\SqlParser\Tests\Lexer;

use PhpMyAdmin\SqlParser\Tests\TestCase;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\SqlParser\TokensList;

use function count;

class TokensListTest extends TestCase
{
    /**
     * ArrayObj of tokens that are used for testing.
     *
     * @var Token[]
     */
    public $tokens;

    /**
     * Test setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tokens = [
            new Token('SELECT', Token::TYPE_KEYWORD),
            new Token(' ', Token::TYPE_WHITESPACE),
            new Token('*', Token::TYPE_OPERATOR),
            new Token(' ', Token::TYPE_WHITESPACE),
            new Token('FROM', Token::TYPE_KEYWORD, Token::FLAG_KEYWORD_RESERVED),
            new Token(' ', Token::TYPE_WHITESPACE),
            new Token('`test`', Token::TYPE_SYMBOL),
            new Token(' ', Token::TYPE_WHITESPACE),
            new Token('WHERE', Token::TYPE_KEYWORD, Token::FLAG_KEYWORD_RESERVED),
            new Token(' ', Token::TYPE_WHITESPACE),
            new Token('name', Token::TYPE_NONE),
            new Token('=', Token::TYPE_OPERATOR),
            new Token('fa', Token::TYPE_NONE),
        ];
    }

    public function testBuild(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals('SELECT * FROM `test` WHERE name=fa', TokensList::build($list));
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
        $this->assertEquals($this->tokens[0], $list->getNextOfType(Token::TYPE_KEYWORD));
        $this->assertEquals($this->tokens[4], $list->getNextOfType([Token::TYPE_KEYWORD]));
        $this->assertEquals($this->tokens[6], $list->getNextOfType([Token::TYPE_KEYWORD, Token::TYPE_SYMBOL]));
        $this->assertEquals($this->tokens[8], $list->getNextOfType([Token::TYPE_KEYWORD, Token::TYPE_SYMBOL]));
        $this->assertNull($list->getNextOfType(Token::TYPE_KEYWORD));
    }

    public function testGetPreviousOfType(): void
    {
        $list = new TokensList($this->tokens);
        $list->idx = 9;
        $this->assertEquals($this->tokens[8], $list->getPreviousOfType([Token::TYPE_KEYWORD, Token::TYPE_SYMBOL]));
        $this->assertEquals($this->tokens[6], $list->getPreviousOfType([Token::TYPE_KEYWORD, Token::TYPE_SYMBOL]));
        $this->assertEquals($this->tokens[4], $list->getPreviousOfType([Token::TYPE_KEYWORD]));
        $this->assertEquals($this->tokens[0], $list->getPreviousOfType(Token::TYPE_KEYWORD));
        $this->assertNull($list->getPreviousOfType(Token::TYPE_KEYWORD));
    }

    public function testGetNextOfTypeAndFlag(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[4], $list->getNextOfTypeAndFlag(
            Token::TYPE_KEYWORD,
            Token::FLAG_KEYWORD_RESERVED
        ));
        $this->assertEquals($this->tokens[8], $list->getNextOfTypeAndFlag(
            Token::TYPE_KEYWORD,
            Token::FLAG_KEYWORD_RESERVED
        ));
        $this->assertNull($list->getNextOfTypeAndFlag(Token::TYPE_KEYWORD, Token::FLAG_KEYWORD_RESERVED));
    }

    public function testGetNextOfTypeAndValue(): void
    {
        $list = new TokensList($this->tokens);
        $this->assertEquals($this->tokens[0], $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'SELECT'));
        $this->assertNull($list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'SELECT'));
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

        // offsetSet($offset, $value) with overflowed offset
        $list[$list->count] = $this->tokens[1];
        $this->assertSame($list[$list->count - 1], $this->tokens[1]);
        $this->assertSame($list->count, count($this->tokens) + 1);

        // offsetGet($offset) with negative offset
        $this->assertNull($list[-1]);

        // offsetGet($offset) with overflow offset
        $this->assertNull($list[$list->count]);

        // offsetGet($offset)
        for ($i = 0, $count = count($this->tokens); $i < $count; ++$i) {
            $this->assertSame($this->tokens[$i], $list[$i]);
        }

        // offsetExists($offset)
        $this->assertArrayHasKey(2, $list);
        $this->assertArrayNotHasKey(14, $list);
        $this->assertArrayNotHasKey(-8, $list);

        // offsetUnset($offset)
        $currentCountTokens = $list->count;
        unset($list[2]);
        $newCountTokens = $list->count;
        $this->assertSame($this->tokens[3], $list[2]);
        $this->assertSame($currentCountTokens - 1, $newCountTokens);

        // offsetUnset($offset) with invalid offset (negative or overflowed)
        $currentListTokens = $list->tokens;
        unset($list[-1]);
        $this->assertSame($currentListTokens, $list->tokens);
        $this->assertSame($newCountTokens, $list->count); // No unset actually performed.
        unset($list[999]);
        $this->assertSame($currentListTokens, $list->tokens);
        $this->assertSame($newCountTokens, $list->count); // No unset actually performed.
    }
}
