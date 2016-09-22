<?php declare(strict_types = 1);

namespace Venta\Framework\Commands;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venta\Console\Command;
use Venta\Contracts\Http\RequestFactory;
use Venta\Contracts\Routing\RouteCollector;
use Venta\Contracts\Routing\RouteMatcher;
use Venta\Routing\Exceptions\NotAllowedException;
use Venta\Routing\Exceptions\NotFoundException;
use Zend\Diactoros\Uri;

/**
 * Class RouteMatch
 *
 * @package Venta\Commands
 */
class RouteMatch extends Command
{

    /**
     * @var RouteCollector
     */
    protected $collector;

    /**
     * @var \Venta\Contracts\Routing\RouteMatcher
     */
    protected $matcher;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * RouteMatch constructor.
     *
     * @param RouteCollector $collector
     * @param \Venta\Contracts\Routing\RouteMatcher $matcher
     * @param RequestFactory $requestFactory
     */
    public function __construct(RouteCollector $collector, RouteMatcher $matcher, RequestFactory $requestFactory)
    {
        parent::__construct();
        $this->collector = $collector;
        $this->matcher = $matcher;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @inheritDoc
     */
    public function description(): string
    {
        return 'Matches provided path against application routes';
    }

    /**
     * @inheritDoc
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        $uri = new Uri($this->arg('path'));
        if ($input->getOption('host')) {
            $uri = $uri->withHost($input->getOption('host'));
        }
        if ($input->getOption('scheme')) {
            $uri = $uri->withScheme($input->getOption('scheme'));
        }
        $request = $this->requestFactory->createServerRequest($input->getOption('method'), $uri);
        try {
            $route = $this->matcher->match($request, $this->collector);
            $table = new Table($output);
            $table->setHeaders(['Methods', 'Path', 'Action', 'Name', 'Host', 'Scheme', 'Middlewares']);
            $table->addRow([
                join(',', $route->getMethods()),
                $route->getPath(),
                is_string($route->getCallable()) ? $route->getCallable() : get_class($route->getCallable()),
                $route->getName(),
                $route->getHost(),
                $route->getScheme(),
                join(',', array_keys($route->getMiddlewares())),
            ]);
            $table->render();
        } catch (NotFoundException $e) {
            $output->writeln('<error>Path cannot be matched against defined routes</error>');
        } catch (NotAllowedException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @inheritDoc
     */
    public function signature(): string
    {
        return 'route:match {path:Uri path to match against} {--method=GET:Specify method} {--host=:Specify host} {--scheme=:Specify scheme}';
    }


}