<?php

namespace Naran\Axis\Tests\Func;


use WP_UnitTestCase;
use function Naran\Axis\Func\closeTag;
use function Naran\Axis\Func\encloseString;
use function Naran\Axis\Func\formatAttrs;
use function Naran\Axis\Func\inputTag;
use function Naran\Axis\Func\listTag;
use function Naran\Axis\Func\openTag;
use function Naran\Axis\Func\optionTag;
use function Naran\Axis\Func\selectTag;

class TestHtmlOutput extends WP_UnitTestCase
{
    public function testEncloseString()
    {
        $this->assertEquals('"test"', encloseString('test'));
        $this->assertEquals(':test:', encloseString('test', ':'));
    }

    public function testFormatAttrs()
    {
        $this->assertEquals(' readonly="readonly"', formatAttrs(['readonly' => true]));
        $this->assertEquals(' ', formatAttrs(['readonly' => false]));
    }

    public function testOpenTag()
    {
        $this->assertEquals('<div class="test-class">', openTag('div', ['class' => 'test-class'], false));
    }

    public function testCloseTag()
    {
        $this->assertEquals('</div>', closeTag('div', false));
    }

    public function testInputTag()
    {
        $this->assertEquals(
            '<input type="text" class="text" value="test">',
            inputTag(['type' => 'text', 'class' => 'text', 'value' => 'test'], false)
        );
    }

    public function testOptionTag()
    {
        $this->assertEquals(
            '<option class="test-class" value="test" selected="selected">Test</option>',
            optionTag('test', 'Test', 'test', ['class' => 'test-class'], false)
        );
    }

    public function testSelectTag()
    {
        $output = selectTag(
            [
                '1' => 'one',
                '2' => 'two',
            ],
            '',
            ['class' => 'test-class'],
            [],
            '-- choose --',
            false
        );

        // remove unnecessary whitespaces.
        $output = str_replace("\n", '', $output);
        $output = preg_replace('/\s{2,}/', ' ', $output);
        $output = preg_replace('/\s+>/', '>', $output);

        $this->assertEquals(
            '<select class="test-class"><option disabled="disabled" selected="selected" value="">-- choose --</option><option value="1">one</option><option value="2">two</option></select>',
            $output
        );
    }

    public function testListTag()
    {
        $output = listTag(
            'ul',
            [],
            'li',
            ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            [],
            true,
            false
        );

        // remove unnecessary whitespaces.
        $output = str_replace("\n", '', $output);
        $output = preg_replace('/\s{2,}/', ' ', $output);
        $output = preg_replace('/\s+>/', '>', $output);

        $this->assertEquals(
            '<ul><li>A</li><li>B</li><li>C</li></ul>',
            $output
        );
    }
}