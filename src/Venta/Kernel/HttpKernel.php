<?php declare(strict_types = 1);

namespace Venta\Kernel;

use Abava\Container\Contract\Caller;
use Abava\Container\Contract\Container;
use Abava\Http\Contract\{
    Emitter as EmitterContract
};
use Abava\Http\Emitter;
use Abava\Routing\Route;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Venta\Contracts\Application;
use Venta\Contracts\Kernel\HttpKernel as HttpKernelContact;

/**
 * Class HttpKernel
 *
 * @package Venta
 */
class HttpKernel implements HttpKernelContact
{
    /**
     * Application instance holder
     *
     * @var Application
     */
    protected $application;

    /**
     * {@inheritdoc}
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        // binding request instance
        if (!$this->application->has('request')) {
            $this->application->singleton('request', $request);
        }
        if (!$this->application->has(RequestInterface::class) && $request instanceof RequestInterface) {
            $this->application->singleton(RequestInterface::class, $request);
        }
        if (!$this->application->has(ServerRequestInterface::class) && $request instanceof ServerRequestInterface) {
            $this->application->singleton(ServerRequestInterface::class, $request);
        }

        // calling ->bindings() on extension providers
        $this->application->bootExtensionProviders();
        $this->defineBindings();

        /** @var \Abava\Routing\Contract\Collector $collector */
        $collector = $this->application->make(\Abava\Routing\Contract\Collector::class);
        $this->application->routes($collector);
        /** @var \Abava\Routing\Contract\Middleware\Collector $middlewareCollector */
        $middlewareCollector = $this->application->make(\Abava\Routing\Contract\Middleware\Collector::class);
        $this->application->middlewares($middlewareCollector);
        /** @var \Abava\Routing\Contract\Matcher $matcher */
        $matcher = $this->application->make(\Abava\Routing\Contract\Matcher::class);
        $route = $matcher->match($request, $collector); // <-- uses Dispatcher to find matching route
        $this->application->singleton('route', $route);
        $this->application->singleton(Route::class, $route);
        foreach ($route->getMiddlewares() as $name => $m) {
            $middlewareCollector->pushMiddleware($name, $m);
        }
        /** @var \Abava\Routing\Contract\Strategy $strategy */
        $strategy = $this->application->make(\Abava\Routing\Contract\Strategy::class);
        $last = function() use ($strategy, $route) { return $strategy->dispatch($route); };
        /** @var \Abava\Routing\Contract\Middleware\Pipeline $middleware */
        $middleware = $this->application->make(\Abava\Routing\Contract\Middleware\Pipeline::class);
        $response = $middleware->handle($request, $last); // <-- here is where all the action begins!

        // bind the latest response instance, it may be used in terminate part
        if (!$this->application->has(ResponseInterface::class)) {
            $this->application->singleton(ResponseInterface::class, $response);
        }
        if (!$this->application->has('response')) {
            $this->application->singleton('response', $response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response)
    {
        /** @var EmitterContract $emitter */
        $emitter = $this->application->make(EmitterContract::class);
        $emitter->emit($response);
    }


    /**
     * {@inheritdoc}
     */
    public function terminate()
    {
        $this->application->terminate();
    }

    /**
     * Bind default implementations to contracts
     *
     * @return void
     */
    protected function defineBindings()
    {
        // binding container
        if (!$this->application->has(Container::class)) {
            $this->application->singleton(Container::class, $this->application);
        }

        // binding caller
        if (!$this->application->has(Caller::class)) {
            $this->application->singleton(Caller::class, $this->application);
        }

        // binding response emitter
        if (!$this->application->has(EmitterContract::class)) {
            $this->application->singleton(EmitterContract::class, Emitter::class);
        }

        // binding route path parser
        if (!$this->application->has(\FastRoute\RouteParser::class)) {
            $this->application->bind(\FastRoute\RouteParser::class, \Abava\Routing\Parser::class);
        }

        // binding route parameter parser
        if (!$this->application->has(\FastRoute\DataGenerator::class)) {
            $this->application->bind(\FastRoute\DataGenerator::class, \FastRoute\DataGenerator\GroupCountBased::class);
        }

        // binding route collector
        if (!$this->application->has(\Abava\Routing\Contract\Collector::class)) {
            $this->application->bind(\Abava\Routing\Contract\Collector::class, \Abava\Routing\Collector::class);
        }

        // binding middleware collector
        if (!$this->application->has(\Abava\Routing\Contract\Middleware\Collector::class)) {
            $this->application->singleton(
                \Abava\Routing\Contract\Middleware\Collector::class,
                \Abava\Routing\Middleware\Collector::class
            );
        }

        // binding middleware pipeline
        if (!$this->application->has(\Abava\Routing\Contract\Middleware\Pipeline::class)) {
            $this->application->bind(
                \Abava\Routing\Contract\Middleware\Pipeline::class,
                \Abava\Routing\Middleware\Pipeline::class
            );
        }

        // binging route matcher
        if (!$this->application->has(\Abava\Routing\Contract\Matcher::class)) {
            $this->application->bind(\Abava\Routing\Contract\Matcher::class, \Abava\Routing\Matcher::class);
        }

        // binding dispatch strategy
        if (!$this->application->has(\Abava\Routing\Contract\Strategy::class)) {
            $this->application->bind(\Abava\Routing\Contract\Strategy::class, \Abava\Routing\Strategy\Generic::class);
        }
    }

}