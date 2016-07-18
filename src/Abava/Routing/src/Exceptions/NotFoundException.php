<?php declare(strict_types = 1);

namespace Abava\Routing\Exceptions;

/**
 * Class NotFoundException
 *
 * @package Abava\Routing
 */
class NotFoundException extends \LogicException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(\Exception $previous = null)
    {
        parent::__construct('Can not route to this URI.', 0, $previous);
    }
}