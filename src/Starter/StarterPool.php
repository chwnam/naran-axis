<?php


namespace Naran\Axis\Starter;

final class StarterPool
{
    /** @var self */
    private static $instance = null;

    /** @var Starter[] */
    private $pool = [];

    private function __construct()
    {
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function __wakeup()
    {
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function __sleep()
    {
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if ( ! static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * 개시자를 추가합니다.
     *
     * @param Starter $starter
     */
    public function addStarter(Starter $starter)
    {
        $this->pool[$starter->getSlug()] = $starter;
    }

    /**
     * 개시자를 반환합니다.
     *
     * @param string $slug
     *
     * @return Starter|null
     */
    public function getStarter(string $slug)
    {
        return $this->pool[$slug] ?? null;
    }
}
