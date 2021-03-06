<?php declare(strict_types = 1);

namespace Venta\Contracts\Routing;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RouteMatcher
 *
 * @package Venta\Contracts\Routing
 */
interface RouteMatcher
{

    /**
     * Matches route collection against provided request.
     *
     * @param ServerRequestInterface $request
     * @param ImmutableRouteCollection $routeCollection
     * @return Route
     */
    public function match(ServerRequestInterface $request, ImmutableRouteCollection $routeCollection): Route;

}