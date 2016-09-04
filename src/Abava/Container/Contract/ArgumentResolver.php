<?php declare(strict_types = 1);

namespace Abava\Container\Contract;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Interface ArgumentResolver
 *
 * @package Abava\Container\Contract
 */
interface ArgumentResolver
{
    /**
     * Create reflector depending on callable type.
     *
     * @param callable|string|array $callable
     * @return ReflectionFunction|ReflectionMethod|ReflectionFunctionAbstract
     */
    public function reflectCallable($callable): ReflectionFunctionAbstract;

    /**
     * Creates argument resolver closure for subject function.
     *
     * @param ReflectionFunctionAbstract $function
     * @return Closure
     */
    public function resolveArguments(ReflectionFunctionAbstract $function): Closure;

}