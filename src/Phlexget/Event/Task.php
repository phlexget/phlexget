<?php

namespace Phlexget\Event;

use Phlexget\Console\Application;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Task extends Event implements \ArrayAccess
{
    protected $application;
    protected $input;
    protected $output;
    protected $config;
    protected $attributes = array();

    public function __construct(Application $application, InputInterface $input, OutputInterface $output, array $config)
    {
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
        $this->config = $config;
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}