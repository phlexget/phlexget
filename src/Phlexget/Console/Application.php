<?php

namespace Phlexget\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;

use Pimple;

use Phlexget\Phlexget;
use Phlexget\Command\PhlexgetCommand;

use Doctrine\Common\Cache\FilesystemCache;

class Application extends BaseApplication
{
    private $container;

    private $homeDir;

    private $cacheDir;

    /**
     * Constructor.
     *
     * @param Pimple $container A Pimple instance
     */
    public function __construct(Pimple $container)
    {
        $this->container = $container;

        parent::__construct('Phlexget', Phlexget::VERSION);
    }

    /**
     * Gets the Container.
     *
     * @return Pimple A Pimple instance
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns application's Home directory.
     *
     * @return string
     */
    public function getHomeDir()
    {
        return $this->homeDir;
    }

    /**
     * Returns application's cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        try {
            $this->find($input->getFirstArgument());
            return parent::getCommandName($input);
        } catch (\InvalidArgumentException $e) {
            $this->getDefinition()->setArguments(array());
            return 'phlexget';
        }
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return array An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        // Keep the core default commands to have the HelpCommand
        // which is used when using the --help option
        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new PhlexgetCommand();

        return $defaultCommands;
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->setup();
        $this->registerCache();
        $this->registerCommands();
        $this->registerPlugins();

        return parent::doRun($input, $output);
    }

    private function setup()
    {
        // determine home and cache dirs
        $home = getenv('PHLEXGET_HOME');
        $cacheDir = getenv('PHLEXGET_CACHE_DIR');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/Phlexget';
            } else {
                $home = rtrim(getenv('HOME'), '/') . '/.phlexget';
            }
        }
        if (!$cacheDir) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if ($cacheDir = getenv('LOCALAPPDATA')) {
                    $cacheDir .= '/Phlexget';
                } else {
                    $cacheDir = getenv('APPDATA') . '/Phlexget/cache';
                }
                $cacheDir = strtr($cacheDir, '\\', '/');
            } else {
                $cacheDir = $home.'/cache';
            }
        }

        // Protect directory against web access. Since HOME could be
        // the www-data's user home and be web-accessible it is a
        // potential security risk
        foreach (array($home, $cacheDir) as $dir) {
            if (!file_exists($dir . '/.htaccess')) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                @file_put_contents($dir . '/.htaccess', 'Deny from all');
            }
        }

        $this->homeDir = $home;
        $this->cacheDir = $cacheDir;
    }

    protected function registerCache()
    {
        $this->container['cache.class'] = 'Doctrine\\Common\\Cache\\FilesystemCache';
        $this->container['cache.dir'] = $this->cacheDir;

        $this->container['cache'] = $this->container->share(function($container){
            return new $container['cache.class'](
                $container['cache.dir'],
                '.cache'
            );
        });
    }

    protected function registerCommands()
    {
        $this->registerApplicationCommands();

        /*foreach ($this->getPlugins() as $plugin) {
            if ($plugin instanceof Plugin) {
                $plugin->registerCommands($this);
            }
        }*/
    }

    /**
     * Finds and registers Application Commands.
     *
     * * Commands are in the 'Command' sub-directory
     * * Commands extend Symfony\Component\Console\Command\Command
     */
    protected function registerApplicationCommands()
    {
        if (!is_dir($dir = __DIR__.'/../Command')) {
            return;
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($dir);

        $prefix = 'Phlexget\\Command';
        foreach ($finder as $file) {
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\'.strtr($relativePath, '/', '\\');
            }
            $r = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
            if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract()) {
                $this->add($r->newInstance());
            }
        }
    }

    protected function registerPlugins()
    {
        if (!is_dir($dir = __DIR__.'/../../../vendor')) {
            return;
        }

        $finder = new Finder();
        $finder->files()->name('*Plugin.php')->in($dir);

        foreach ($finder as $file) {
            if ($relativePath = $file->getRelativePath()) {
                $ns = preg_replace('#[a-zA-Z0-9-]+/[a-zA-Z0-9-]+/#', '', $relativePath);
                $ns = '\\'.strtr($ns, '/', '\\');
            }

            $r = new \ReflectionClass($ns.'\\'.$file->getBasename('.php'));
            if ($r->isSubclassOf('Phlexget\\Plugin\\AbstractPlugin') && !$r->isAbstract()) {
                $plugin = $r->newInstance();
                $plugin->setContainer($this->container);
                $this->container['event_dispatcher']->addSubscriber($plugin);
            }
        }
    }
}