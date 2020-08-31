<?php


namespace Naran\Axis\Object;

/**
 * Class Script
 *
 * @package Naran\Axis\Object
 */
final class Script
{
    /**
     * @var string
     */
    public $handle = '';

    /**
     * @var string
     */
    public $src = '';

    /**
     * @var string[]
     */
    public $deps = [];

    /**
     * bool: Use WordPress Version.
     * null: Do not append version to query string.
     *
     * @var string|bool|null
     */
    public $ver = false;

    /**
     * @var bool
     */
    public $inFooter = false;

    /**
     * Extra attributes appended to <script> tag.
     *
     * @var array
     */
    public $attrs = [];

    public function __construct($handle, $src, $deps = [], $ver = false, $inFooter = false, $attrs = [])
    {
        $this->handle   = $handle;
        $this->src      = $src;
        $this->deps     = $deps;
        $this->ver      = $ver;
        $this->inFooter = $inFooter;
        $this->attrs    = $attrs;
    }
}
