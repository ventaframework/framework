<?php

use Abava\Routing\Contract\Middleware;
use Abava\Routing\Contract\Middleware\Collector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Venta\Commands\Middlewares;

/**
 * Class MiddlewaresTest
 */
class MiddlewaresTest extends TestCase
{

    /**
     * @test
     */
    public function canHandleMiddlewareList()
    {
        $collector = Mockery::mock(Collector::class);
        $collector->shouldReceive('rewind')->withNoArgs()->once();
        $collector->shouldReceive('valid')->withNoArgs()->andReturn(true, false);
        $collector->shouldReceive('key')->withNoArgs()->andReturn('middleware')->once();
        $collector->shouldReceive('current')->withNoArgs()->andReturn(Mockery::mock(Middleware::class))->once();
        $collector->shouldReceive('next')->withNoArgs()->once();

        $command = new Middlewares($collector);
        $command->handle(new ArrayInput([]), $output = new BufferedOutput());

        $this->assertContains('middleware', $output->fetch());
    }

    public function tearDown()
    {
        Mockery::close();
    }

}
