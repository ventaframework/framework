<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Venta\Contracts\Routing\Middleware;
use Venta\Contracts\Routing\MiddlewareCollector;
use Venta\Framework\Commands\Middlewares;

/**
 * Class MiddlewaresTest
 */
class MiddlewaresTest extends TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function canHandleMiddlewareList()
    {
        $collector = Mockery::mock(MiddlewareCollector::class);
        $collector->shouldReceive('rewind')->withNoArgs()->once();
        $collector->shouldReceive('valid')->withNoArgs()->andReturn(true, false);
        $collector->shouldReceive('key')->withNoArgs()->andReturn('middleware')->once();
        $collector->shouldReceive('current')->withNoArgs()->andReturn(Mockery::mock(Middleware::class))->once();
        $collector->shouldReceive('next')->withNoArgs()->once();

        $command = new Middlewares($collector);
        $command->handle(new ArrayInput([]), $output = new BufferedOutput());

        $this->assertContains('middleware', $output->fetch());
    }

}