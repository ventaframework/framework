<?php declare(strict_types = 1);

namespace Abava\Routing\Contract\Dispatcher;

use FastRoute\Dispatcher;

/**
 * Interface Factory
 *
 * @package Abava\Routing\Contract\Dispatcher
 */
interface DispatcherFactory
{

    /**
     * Make dispatcher instance and pass $data array
     *
     * @param array $data
     * @return Dispatcher
     */
    public function create(array $data): Dispatcher;

}