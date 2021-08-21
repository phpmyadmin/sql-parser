<?php

namespace PhpMyAdmin\SqlParser\Tests\Components;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\Key;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Tests\TestCase;

class KeyTest extends TestCase
{
    public function testParse()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('')
        );
        $this->assertNull($component->type);
        $this->assertNull($component->options);
        $this->assertNull($component->name);
        $this->assertNull($component->expr);
        $this->assertSame(array(), $component->columns);
    }

    public function testParseKeyWithoutOptions()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame(array(
            array(
                'name' => 'alias_type',
            )
        ), $component->columns);
    }

    public function testParseKeyWithLengthWithoutOptions()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`(10)),')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame(array(
            array(
                'name' => 'alias_type',
                'length' => 10,
            )
        ), $component->columns);
    }

    public function testParseKeyWithLengthWithOptions()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList('KEY `alias_type_idx` (`alias_type`(10)) COMMENT \'my comment\',')
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('alias_type_idx', $component->name);
        $this->assertEquals(new OptionsArray(
            array(
                4 => array(
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                )
            )
        ), $component->options);
        $this->assertNull($component->expr);
        $this->assertSame(array(
            array(
                'name' => 'alias_type',
                'length' => 10,
            )
        ), $component->columns);
    }

    public function testParseKeyExpressionWithoutOptions()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList(
                'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))),'
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(), $component->options);
        $expr = new Expression(
            '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))'
        );
        $expr->function = 'convert_tz';
        $this->assertEquals(
            $expr,
            $component->expr
        );
        $this->assertSame(array(), $component->columns);
    }

    public function testParseKeyExpressionWithOptions()
    {
        $component = Key::parse(
            new Parser(),
            $this->getTokensList(
                'KEY `updated_tz_ind2` ((convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))) COMMENT \'my comment\','
            )
        );
        $this->assertEquals('KEY', $component->type);
        $this->assertEquals('updated_tz_ind2', $component->name);
        $this->assertEquals(new OptionsArray(
            array(
                4 => array(
                    'name' => 'COMMENT',
                    'equals' => false,
                    'expr' => '\'my comment\'',
                    'value' => 'my comment',
                )
            )
        ), $component->options);
        $expr = new Expression(
            '(convert_tz(`cache_updated`,_utf8mb4\'GMT\',_utf8mb4\'GB\'))'
        );
        $expr->function = 'convert_tz';
        $this->assertEquals(
            $expr,
            $component->expr
        );
        $this->assertSame(array(), $component->columns);
    }
}
