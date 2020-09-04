<?php


namespace Naran\Axis\Tests\Func;


use WP_UnitTestCase;
use function Naran\Axis\Func\getUrlPath;
use function Naran\Axis\Func\strEndsWith;
use function Naran\Axis\Func\strStartsWith;
use function Naran\Axis\Func\toCamelCase;
use function Naran\Axis\Func\toPascalCase;
use function Naran\Axis\Func\toSnakeCase;

class TestUtil extends WP_UnitTestCase
{
    public function testStrStartsWith()
    {
        $this->assertTrue(strStartsWith('TestString', ''));
        $this->assertTrue(strStartsWith('TestString', 'Test'));
        $this->assertFalse(strStartsWith('TestString', 'Str'));
    }

    public function testStrEndsWith()
    {
        $this->assertTrue(strEndsWith('TestString', ''));
        $this->assertTrue(strEndsWith('TestString', 'ring'));
        $this->assertFalse(strEndsWith('TestString', 'rind'));
    }

    public function testToSnakeCase()
    {
        $this->assertEquals(
            'this_is_a_snake_cased_sentence',
            toSnakeCase('ThisIsASnakeCasedSentence')
        );

        $this->assertEquals(
            'this_is_a_snake_cased_sentence',
            toSnakeCase('thisIsASnakeCasedSentence')
        );
    }

    public function testToPascalCase()
    {
        $this->assertEquals(
            'ThisIsAPascalCasedSentence',
            toPascalCase('this_is_a_pascal_cased_sentence')
        );

        $this->assertEquals(
            'ThisIsAPascalCasedSentence',
            toPascalCase('thisIsAPascalCasedSentence')
        );
    }

    public function testToCamelCase()
    {
        $this->assertEquals(
            'thisIsAPascalCasedSentence',
            toCamelCase('this_is_a_pascal_cased_sentence')
        );

        $this->assertEquals(
            'thisIsAPascalCasedSentence',
            toCamelCase('ThisIsAPascalCasedSentence')
        );
    }

    public function testGetUrlPath()
    {
        $this->assertEquals(
            '/test-path',
            getUrlPath('http://sample.com/test-path')
        );

        $this->assertEquals(
            '/test-path',
            getUrlPath('/test-path')
        );

        $this->assertEquals(
            'test-path',
            getUrlPath('test-path')
        );
    }
}
