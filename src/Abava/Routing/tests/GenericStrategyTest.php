<?php

class GenericStrategyTest extends \PHPUnit_Framework_TestCase
{

    protected $caller;
    protected $route;
    protected $response;
    protected $factory;

    public function setUp()
    {
        $this->caller = Mockery::mock(\Abava\Container\Contract\Caller::class);
        $this->response = Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $this->route = (new \Abava\Routing\Route(['GET'], '/url', 'controller@action'))
            ->withParameters(['param' => 'value']);
        $this->factory = Mockery::mock(\Abava\Http\Factory\ResponseFactory::class);
    }

    public function testResponseInterfaceResult()
    {
        $this->caller->shouldReceive('call')
            ->with($this->route->getCallable(), $this->route->getParameters())
            ->andReturn($this->response)
            ->once();
        $strategy = new \Abava\Routing\Strategy\Generic($this->caller, $this->factory);
        $result = $strategy->dispatch($this->route);

        $this->assertSame($this->response, $result);
    }

    public function testStringableResult()
    {
        $this->caller->shouldReceive('call')
             ->with($this->route->getCallable(), $this->route->getParameters())
             ->andReturn(new class {
                 public function __toString() { return 'string'; }
             })
             ->once();
        // todo check of can be replaced with contract
        $ventaResponse = Mockery::mock(\Abava\Http\Response::class);
        $ventaResponse->shouldReceive('append')->with('string')->andReturn($ventaResponse)->once();
        $this->factory->shouldReceive('new')->withNoArgs()->andReturn($ventaResponse);
        // todo check why "new" method mock is not enough
        $this->factory->shouldReceive('createResponse')->withNoArgs()->andReturn($ventaResponse);
        $strategy = new \Abava\Routing\Strategy\Generic($this->caller, $this->factory);
        $result = $strategy->dispatch($this->route);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result);
    }

    public function testInvalidCallerResult()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Controller action result must be either ResponseInterface or string');

        $this->caller->shouldReceive('call')
                     ->with($this->route->getCallable(), $this->route->getParameters())
                     ->andReturn(new stdClass)
                     ->once();
        $strategy = new \Abava\Routing\Strategy\Generic($this->caller, $this->factory);
        $result = $strategy->dispatch($this->route);
    }

    public function tearDown()
    {
        Mockery::close();
    }

}
