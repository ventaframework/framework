<?php declare(strict_types = 1);

namespace Venta\Framework\Contracts\Kernel;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface KernelContract
 *
 * @package Venta\Framework
 */
interface HttpKernelContract extends AbstractKernelContract
{
    /**
     * Main handle function for application
     *
     * @param  RequestInterface $request
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request): ResponseInterface;

    /**
     *
     *
     * @param  RequestInterface $request
     * @param  ResponseInterface $response
     * @return mixed
     */
    public function terminate(RequestInterface $request, ResponseInterface $response);
}