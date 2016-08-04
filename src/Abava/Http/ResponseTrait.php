<?php declare(strict_types = 1);

namespace Abava\Http;

use Abava\Http\Contract\Response as ResponseContract;
use Psr\Http\Message\StreamInterface;

/**
 * Class ResponseTrait
 *
 * @package Abava\Http
 * @method StreamInterface getBody()
 */
trait ResponseTrait
{
    /**
     * Writes provided string to response body stream
     *
     * @param string $body
     * @return ResponseContract
     */
    public function append(string $body): ResponseContract
    {
        $this->getBody()->write($body);

        return $this;
    }
}