<?php


namespace Naran\Axis\Cli;


interface Template
{
    public function getContent(string $template, array $context): string;

    public function write(string $file, string $content): bool;
}
