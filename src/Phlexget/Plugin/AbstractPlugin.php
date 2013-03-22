<?php

namespace Phlexget\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pimple;

abstract class AbstractPlugin implements EventSubscriberInterface
{
    protected $container;

    /**
     * Sets the container
     *
     * @param Pimple $container
     */
    public function setContainer(Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a service/parameter from the container
     *
     * @param string $name Service/Parameter name
     * @return mixed
     */
    public function get($name)
    {
        return $this->container[$name];
    }
}