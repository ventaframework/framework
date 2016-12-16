<?php declare(strict_types = 1);

namespace Venta\Framework\Kernel;

use Venta\Contracts\Container\Container;
use Venta\Contracts\Kernel\Kernel;

/**
 * Class AbstractKernelBootstrap
 *
 * @package Venta\Framework\Kernel
 */
abstract class AbstractKernelBootstrap
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * AbstractKernelBootstrap constructor.
     *
     * @param Container $container
     * @param Kernel $kernel
     */
    public function __construct(Container $container, Kernel $kernel)
    {
        $this->container = $container;
        $this->kernel = $kernel;
    }

    /**
     * Runs the Bootstrap.
     *
     * @return void
     */
    abstract public function __invoke();

    /**
     * @return Container
     */
    protected function container(): Container
    {
        return $this->container;
    }

    /**
     * @return Kernel
     */
    protected function kernel(): Kernel
    {
        return $this->kernel;
    }
}