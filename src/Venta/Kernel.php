<?php declare(strict_types = 1);

namespace Venta;

use Abava\Config\Config;
use Abava\Config\Contract\Config as ConfigContract;
use Abava\Config\Contract\Factory;
use Abava\Console\Command\Collector as CommandCollector;
use Abava\Console\Contract\Collector as CommandCollectorContract;
use Abava\Container\Contract\Container;
use Abava\Routing\Collector as RouteCollector;
use Abava\Routing\Contract\Collector as RouteCollectorContract;
use Abava\Routing\Contract\Middleware\Collector as MiddlewareCollectorContract;
use Abava\Routing\Middleware\Collector as MiddlewareCollector;
use Dotenv\Dotenv;
use RuntimeException;
use Venta\Contract\ExtensionProvider\CommandProvider as CommandsProvider;
use Venta\Contract\ExtensionProvider\ConfigProvider;
use Venta\Contract\ExtensionProvider\MiddlewareProvider as MiddlewaresProvider;
use Venta\Contract\ExtensionProvider\RouteProvider as RoutesProvider;
use Venta\Contract\ExtensionProvider\ServiceProvider as BindingsProvider;

/**
 * Class Kernel
 *
 * @package Venta
 */
class Kernel implements \Venta\Contract\Kernel
{

    /**
     * DI Container instance
     *
     * @var Container
     */
    protected $container;

    /**
     * Array of defined extension providers
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * File with array of extension providers
     *
     * @var string
     */
    protected $extensionsFile;

    /**
     * Project root absolute path
     *
     * @var string
     */
    protected $rootPath;

    /**
     * Version string holder
     *
     * @var string
     */
    protected $version = '0.0.1-hopefully-β-than-M2';

    /**
     * Kernel constructor.
     *
     * @param Container $container
     * @param string $rootPath
     * @param string $extensionsFile
     */
    public function __construct(
        Container $container,
        string $rootPath,
        string $extensionsFile = 'bootstrap/extensions.php'
    ) {
        $this->container = $container;
        $this->rootPath = $rootPath;
        $this->extensionsFile = $extensionsFile;

        /*
         * Binding basic singletons - container and kernel objects
         */
        $container->share(Container::class, $container, ['container']);
        $container->share(\Venta\Contract\Kernel::class, $this, ['kernel']);
    }

    /**
     * @inheritDoc
     */
    public function boot(): Container
    {
        /*
        * Load environment specific configuration from local .env file
        */
        (new Dotenv($this->rootPath))->load();

        /*
        * Find and load extension providers
        */
        $this->loadExtensionProviders();

        // We collect route only on actual access to route collector
        // This defers iterating through extension providers as much as possible
        $this->container->share(RouteCollectorContract::class, function () {
            $collector = $this->container->get(RouteCollector::class);
            $this->collectRoutes($collector);

            return $collector;
        });

        // Collecting middleware in a deferred way
        $this->container->share(MiddlewareCollectorContract::class, function () {
            $collector = $this->container->get(MiddlewareCollector::class);
            $this->collectMiddlewares($collector);

            return $collector;
        });

        // Collecting console commands in a deferred way
        $this->container->share(CommandCollectorContract::class, function () {
            $collector = $this->container->get(CommandCollector::class);
            $this->collectCommands($collector);

            return $collector;
        });

        // Collect configs from extension providers on first Config access
        $this->container->share(ConfigContract::class, function () {
            $config = $this->collectConfig($this->container->get(Factory::class));
            // Locking config for further modifications after we merged it with all extension providers
            $config->lock();

            return $config;
        }, ['config']);

        // Collect services for DI container from extension providers
        $this->collectServices($this->container);

        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function getEnvironment(): string
    {
        return getenv('APP_ENV') ? getenv('APP_ENV') : 'local';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @inheritDoc
     */
    public function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Add extension provider to application
     *
     * @param string $provider
     */
    protected function addExtensionProvider(string $provider)
    {
        if (!isset($this->extensions[$provider])) {
            $this->extensions[$provider] = new $provider;
        }
    }

    /**
     * Collects extension providers' commands
     *
     * @param CommandCollectorContract $collector
     * @return void
     */
    protected function collectCommands(CommandCollectorContract $collector)
    {
        foreach ($this->extensions as $provider) {
            if ($provider instanceof CommandsProvider) {
                $provider->provideCommands($collector);
            }
        }
    }

    /**
     * Collects and merges config from extension providers
     *
     * @param Factory $factory
     * @return Config
     */
    protected function collectConfig(Factory $factory)
    {
        $config = $this->container->get(Config::class);
        foreach ($this->extensions as $provider) {
            if ($provider instanceof ConfigProvider) {
                $config = $config->merge($provider->provideConfig($factory));
            }
        }

        return $config;
    }

    /**
     * Collects extension providers' middlewares
     *
     * @param MiddlewareCollectorContract $collector
     * @return void
     */
    protected function collectMiddlewares(MiddlewareCollectorContract $collector)
    {
        foreach ($this->extensions as $provider) {
            if ($provider instanceof MiddlewaresProvider) {
                $provider->provideMiddlewares($collector);
            }
        }
    }

    /**
     * Collects extension providers' routes
     *
     * @param RouteCollectorContract $collector
     */
    protected function collectRoutes(RouteCollectorContract $collector)
    {
        foreach ($this->extensions as $provider) {
            if ($provider instanceof RoutesProvider) {
                $collector->group('/', [$provider, 'provideRoutes']);
            }
        }
    }

    /**
     * Collects extension providers' bindings
     *
     * @param Container $container
     * @return void
     */
    protected function collectServices(Container $container)
    {
        foreach ($this->extensions as $provider) {
            if ($provider instanceof BindingsProvider) {
                $provider->setServices($container);
            }
        }
    }

    /**
     * Loads all extension providers from extensions file
     */
    protected function loadExtensionProviders()
    {
        $path = $this->rootPath . '/' . $this->extensionsFile;

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf('Extensions file "%s" does not exist', $path));
        }
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Extensions file "%s" is not a regular file', $path));
        }
        if (!is_readable($path)) {
            throw new RuntimeException(sprintf('Extensions file "%s" is not readable', $path));
        }

        // requiring extension providers array
        $providers = require $path;

        if (!is_array($providers)) {
            throw new RuntimeException(sprintf('Extensions file "%s" must return array of class names', $path));
        }

        foreach ($providers as $provider) {
            $this->addExtensionProvider($provider);
        }
    }

}