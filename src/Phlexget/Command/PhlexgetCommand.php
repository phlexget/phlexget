<?php

namespace Phlexget\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Yaml\Yaml;

use Phlexget\Event\Task;

class PhlexgetCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('phlexget')
            ->setDescription('Run phlexget tasks')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();
        $config = Yaml::parse(getcwd().'/config.yml');

        foreach ($config['tasks'] as $taskName => $taskConfig) {
            $task = new Task(
                $this->getApplication(),
                $input,
                $output,
                $taskConfig
            );
            $container['event_dispatcher']->dispatch('phlexget.prepare', $task);

            $container['event_dispatcher']->dispatch('phlexget.input', $task);

            //var_dump($event['xml']);

            $container['event_dispatcher']->dispatch('phlexget.metadata', $task);
            $container['event_dispatcher']->dispatch('phlexget.filter', $task);
            $container['event_dispatcher']->dispatch('phlexget.output', $task);
        }
    }
}