<?php declare(strict_types = 1);

namespace Abava\Config;

use Abava\Config\Contract\Config as ConfigContract;
use Abava\Config\Contract\Factory as FactoryContract;
use Abava\Config\Parser\Json;

/**
 * Class Factory
 *
 * @package Abava\Config
 */
class Factory implements FactoryContract
{

    /**
     * @inheritDoc
     */
    public function fromFile($filename): ConfigContract
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist or is not readable', $filename));
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'php':
                return new Config(include $filename);
            case 'json':
                return (new Json())->parse(file_get_contents($filename));
            default:
                throw new \RuntimeException(sprintf('Unknown config format "%s"', $extension));
        }
    }

}