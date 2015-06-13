<?php

namespace SqlParser\Tests\Lexer;

use SqlParser\Token;
use SqlParser\TokensList;

use SqlParser\Tests\TestCase;

class TokensListCase extends TestCase
{

    /**
     * Array of tokens that are used for testing.
     *
     * @var Token[]
     */
    public $tokens;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tokens = array(
            new Token('SELECT', Token::TYPE_KEYWORD),
            new Token('*', Token::TYPE_OPERATOR),
            new Token('FROM', Token::TYPE_KEYWORD),
            new Token('`test`', Token::TYPE_SYMBOL)
        );
    }

    /**
     * Gets a list used for testing.
     *
     * @return TokensList
     */
    public function getList()
    {
        $list = new TokensList();
        foreach ($this->tokens as $token) {
            $list[] = $token;
        }
        return $list;
    }

    public function testAdd()
    {
        $list = new TokensList();
        foreach ($this->tokens as $token) {
            $list->add($token);
        }
        $this->assertEquals($this->getList(), $list);
    }

    public function testGetNext()
    {
        $list = $this->getList();
        $this->assertEquals($this->tokens[0], $list->getNext());
        $this->assertEquals($this->tokens[1], $list->getNext());
        $this->assertEquals($this->tokens[2], $list->getNext());
        $this->assertEquals($this->tokens[3], $list->getNext());
        $this->assertEquals(null, $list->getNext());
    }

    public function testGetNextOfType()
    {
        $list = $this->getList();
        $this->assertEquals($this->tokens[0], $list->getNextOfType(Token::TYPE_KEYWORD));
        $this->assertEquals($this->tokens[2], $list->getNextOfType(Token::TYPE_KEYWORD));
        $this->assertEquals(null, $list->getNextOfType(Token::TYPE_KEYWORD));
    }

    public function testGetNextOfTypeAndValue()
    {
        $list = $this->getList();
        $this->assertEquals($this->tokens[0], $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'SELECT'));
        $this->assertEquals(null, $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'SELECT'));
    }

    public function testArrayAccess()
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
        $this->assertTrue(isset($list[2]));
        $this->assertFalse(isset($list[5]));

        // offsetUnset($offset)
        unset($list[2]);
        $this->assertEquals($this->tokens[3], $list[2]);

    }
}
