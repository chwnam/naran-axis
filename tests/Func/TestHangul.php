<?php


namespace Naran\Axis\Tests\Func;


use WP_UnitTestCase;
use function Naran\Axis\Func\hangulDecompose;
use function Naran\Axis\Func\hangulPostposition;

class TestHangul extends WP_UnitTestCase
{
    /**
     * @dataProvider decomposeProvider
     *
     * @param string       $input    Test data input.
     * @param array|string $expected Correct answer.
     */
    public function testHangulDecompose($input, $expected)
    {
        $this->assertSame($expected, hangulDecompose($input));
    }

    public function decomposeProvider()
    {
        return [
            // case 1
            [
                '간',
                [
                    ['ㄱ', 'ㅏ', 'ㄴ']
                ]
            ],
            // case 2
            [
                '보성 1982',
                [
                    ['ㅂ', 'ㅗ', ''],
                    ['ㅅ', 'ㅓ', 'ㅇ'],
                    ' ',
                    '1',
                    '9',
                    '8',
                    '2'
                ]
            ],
            // case 3
            [
                '면봉은 q-tip 이지요.',
                [
                    ['ㅁ', 'ㅕ', 'ㄴ'],
                    ['ㅂ', 'ㅗ', 'ㅇ'],
                    ['ㅇ', 'ㅡ', 'ㄴ'],
                    ' ',
                    'q',
                    '-',
                    't',
                    'i',
                    'p',
                    ' ',
                    ['ㅇ', 'ㅣ', ''],
                    ['ㅈ', 'ㅣ', ''],
                    ['ㅇ', 'ㅛ', ''],
                    '.',
                ]
            ]
        ];
    }

    /**
     * @dataProvider hangulPostpositionProvider
     *
     * @param string $input    Test string.
     * @param string $a        Postposition for final-consonant.
     * @param string $b        Postposition for non-final-consonant.
     * @param string $expected Correct answer.
     */
    public function testHangulPostposition($input, $a, $b, $expected)
    {
        $this->assertSame($expected, hangulPostposition($input, $a, $b));
    }

    public function hangulPostpositionProvider()
    {
        return [
            // case 1
            ['풍선', '과', '와', '풍선과'],
            ['보리', '과', '와', '보리와'],

            // case 2
            ['풍선', '을', '를', '풍선을'],
            ['보리', '을', '를', '보리를'],

            // case 3
            ['풍선', '이', '가', '풍선이'],
            ['보리', '이', '가', '보리가'],

            // case 4
            ['풍선', '은', '는', '풍선은'],
            ['보리', '은', '는', '보리는'],

            // case 5
            ['풍선', '으로', '로', '풍선으로'],
            ['보리', '으로', '로', '보리로'],
        ];
    }
}