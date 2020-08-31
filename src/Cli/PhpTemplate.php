<?php


namespace Naran\Axis\Cli;


class PhpTemplate implements Template
{
    public function __construct(string $template, array $context, string $file)
    {
        $this->write($file, $this->getContent($template, $context));
    }

    public function getContent(string $template, array $context): string
    {
        $path = __DIR__ . '/templates/' . trim($template, '/');

        ob_start();

        if (file_exists($path)) {
            extract($context, EXTR_SKIP);
            /** @noinspection PhpIncludeInspection */
            include $path;
        }

        return ob_get_clean();
    }

    public function write(string $file, string $content): bool
    {
        return boolval(file_put_contents($file, $content));
    }
}