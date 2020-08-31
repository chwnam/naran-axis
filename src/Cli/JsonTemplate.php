<?php


namespace Naran\Axis\Cli;


class JsonTemplate extends PhpTemplate
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(array $context, string $file)
    {
        $this->write($file, $this->getContent('json.php', $context));
    }
}
