<?php

use Abava\Console\Command;
use Abava\Console\Contract\Collector;
use Abava\Container\Contract\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Venta\Contract\Kernel;
use Whoops\RunInterface;

/**
 * Class ConsoleApplicationTest
 */
class ConsoleApplicationTest extends TestCase
{

    /**
     * @test
     */
    public function canBootKernel()
    {
        $kernel = Mockery::mock(Kernel::class);
        $kernel->shouldReceive('boot')->withNoArgs()->andReturn(Mockery::mock(Container::class))->once();
        $kernel->shouldReceive('getVersion')->withNoArgs()->andReturn('test')->once();

        $app = new \Venta\Application\ConsoleApplication($kernel);

        $this->assertSame('Venta', $app->getName());
        $this->assertSame('test', $app->getVersion());
    }

    /**
     * @test
     */
    public function canRunApplication()
    {
        // Creating mock stubs
        $kernel = Mockery::mock(Kernel::class);
        $container = Mockery::mock(Container::class);
        $collector = Mockery::mock(Collector::class);

        // Creating input and output for test environment
        $input = new ArrayInput([]);
        $output = new NullOutput();

        // Mocking stub method calls
        $kernel->shouldReceive('boot')->withNoArgs()->andReturn($container)->once();
        $kernel->shouldReceive('getVersion')->withNoArgs()->andReturn('test')->once();
        $container->shouldReceive('bind')->with(InputInterface::class, $input)->once();
        $container->shouldReceive('bind')->with(OutputInterface::class, $output)->once();
        $container->shouldReceive('get')->with(Collector::class)->andReturn($collector)->once();
        $container->shouldReceive('get')->with(InputInterface::class)->andReturn($input)->once();
        $container->shouldReceive('get')->with(OutputInterface::class)->andReturn($output)->once();
        $collector->shouldReceive('getCommands')->withNoArgs()->andReturn([new class extends Command {

            public function signature(): string
            {
                return 'test';
            }

            public function description(): string
            {
                return 'test command';
            }

            public function handle(InputInterface $input, OutputInterface $output)
            {
                // this is faux command that does nothing
            }

        }])->once();

        // Creating and running application
        $app = new \Venta\Application\ConsoleApplication($kernel);
        $app->setAutoExit(false);
        $app->run($input, $output);
    }

    /**
     * @test
     */
    public function canHandleAndRenderException()
    {
        // Creating mocks
        $kernel = Mockery::mock(Kernel::class);
        $container = Mockery::mock(Container::class);
        $errorHandler = Mockery::mock(RunInterface::class);
        $exception = new Exception('Exception message');
        $output = new BufferedOutput();

        // Mocking method calls
        $kernel->shouldReceive('boot')->withNoArgs()->andReturn($container)->once();
        $kernel->shouldReceive('getVersion')->withNoArgs()->andReturn('test')->once();
        $container->shouldReceive('has')->with('error_handler')->andReturn(true)->once();
        $container->shouldReceive('get')->with('error_handler')->andReturn($errorHandler)->once();
        $errorHandler->shouldReceive('handleException')->with($exception)->once();
        $errorHandler->shouldIgnoreMissing();

        // Creating application and handling exception
        $app = new \Venta\Application\ConsoleApplication($kernel);
        // We expect nothing to be echoed directly to output
        // Every single string should be passed to output object
        $this->expectOutputString('');
        $app->renderException($exception, $output);

        // Assert output contains exception message (exception is actually rendered)
        $this->assertContains('Exception message', $output->fetch());
    }

    public function tearDown()
    {
        Mockery::close();
    }

}