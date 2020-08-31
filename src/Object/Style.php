<?php


namespace Naran\Axis\Object;


/**
 * Class Style
 *
 * @package Naran\Axis\Object
 */
final class Style
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
     * @var string
     */
    public $media = 'all';

    /**
     * Extra attributes appended to <style> tag.
     *
     * @var array
     */
    public $attrs = [];

    public function __construct($handle, $src, $deps = [], $ver = false, $media = 'all', $attrs = [])
    {
        $this->handle = $handle;
        $this->src    = $src;
        $this->deps   = $deps;
        $this->ver    = $ver;
        $this->media  = $media;
        $this->attrs  = $attrs;
    }
}
