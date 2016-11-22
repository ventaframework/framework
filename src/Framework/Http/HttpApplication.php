<?php declare(strict_types = 1);

namespace Venta\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;
use Venta\Contracts\Config\Config;
use Venta\Contracts\Container\Container;
use Venta\Contracts\Http\ResponseEmitter as EmitterContract;
use Venta\Contracts\Kernel\Kernel;
use Venta\Contracts\Routing\MiddlewarePipelineFactory;
use Venta\Contracts\Routing\Router;


/**
 * Class HttpApplication
 *
 * @package Venta\Framework\Http
 */
final class HttpApplication
{

    /**
     * @var Container
     */
    private $container;

    /**
     * HttpApplication constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->container = $kernel->boot();
    }

    /**
     * Returns service container instance.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function run(ServerRequestInterface $request)
    {
        /** @var MiddlewarePipelineFactory $factory */
        $factory = $this->container->get(MiddlewarePipelineFactory::class);
        $pipeline = $factory->create($this->container->get(Config::class)->get('middlewares', []));
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        $response = $pipeline->process($request, $router);

        /** @var EmitterContract $emitter */
        $emitter = $this->container->get(EmitterContract::class);
        $emitter->emit($response);
    }

}