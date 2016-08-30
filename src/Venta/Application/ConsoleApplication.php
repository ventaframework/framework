<?php declare(strict_types = 1);

namespace Venta\Application;

use Abava\Console\Contract\Collector;
use Abava\Container\Contract\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venta\Contract\Kernel;

/**
 * Class ConsoleApplication
 *
 * @package Venta
 */
class ConsoleApplication extends Application implements \Venta\Contract\Application\ConsoleApplication
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * HttpApplication constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->container = $kernel->boot();
        parent::__construct('Venta', $kernel->getVersion());
    }

    /**
     * @inheritDoc
     */
    final public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        /*
        |--------------------------------------------------------------------------
        | Bind input
        |--------------------------------------------------------------------------
        |
        | Rebind input instance, if passed as argument
        */
        if ($input) {
            $this->container->set(InputInterface::class, $input);
        }

        /*
        |--------------------------------------------------------------------------
        | Bind output
        |--------------------------------------------------------------------------
        |
        | Rebind output instance, if passed as argument
        */
        if ($output) {
            $this->container->set(OutputInterface::class, $output);
        }

        /*
        |--------------------------------------------------------------------------
        | Add commands
        |--------------------------------------------------------------------------
        |
        | Add collected commands to application
        */
        /** @var Collector $collector */
        $collector = $this->container->get(Collector::class);
        $this->addCommands($collector->getCommands());

        /*
        |--------------------------------------------------------------------------
        | Run application
        |--------------------------------------------------------------------------
        |
        | Run console application using bound Input and Output instances
        */
        parent::run(
            $this->container->get(InputInterface::class),
            $this->container->get(OutputInterface::class)
        );
    }

    /**
     * @param \Exception $e
     * @param OutputInterface $output
     * @return void
     */
    public function renderException(\Exception $e, OutputInterface $output)
    {
        if ($this->container->has('error_handler')) {
            /** @var \Whoops\RunInterface $run */
            $run = $this->container->get('error_handler');
            // from now on ConsoleApplication will render exception
            $run->allowQuit(false);
            $run->writeToOutput(false);
            // Ignore the return string, parent call will render exception
            $run->handleException($e);
        }
        parent::renderException($e, $output);
    }

}