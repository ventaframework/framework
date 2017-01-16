<?php

use PHPUnit\Framework\TestCase;

/**
 * Class MutableContainerTest
 */
class MutableContainerTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @test
     */
    public function canApplyInflectionsOnGet()
    {
        $container = new Venta\Container\MutableContainer;
        $container->addInflection(TestClass::class, 'setValue', [42]);
        $result = $container->get(TestClass::class);

        $this->assertSame(42, $result->getValue());
    }

    /**
     * @test
     */
    public function canApplyInflectionsOnManyInstances()
    {
        $container = new Venta\Container\MutableContainer;
        $container->addInflection(TestClass::class, 'setValue', [42]);
        $test1 = $container->get(TestClass::class);
        $test2 = $container->get(TestClass::class);
        $test3 = $container->get(TestClass::class);

        $this->assertSame(42, $test1->getValue());
        $this->assertSame(42, $test2->getValue());
        $this->assertSame(42, $test3->getValue());
    }

    /**
     * @test
     */
    public function canCallCallableFunctionName()
    {
        $container = new Venta\Container\MutableContainer;
        $this->assertInstanceOf(TestClassContract::class, $container->call('createTestClass'));
    }

    /**
     * @test
     */
    public function canCallClassNameMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $result = $container->call('TestClassFactory::createAndSetValue');

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodFromArray()
    {
        $container = new Venta\Container\MutableContainer;
        $result = $container->call(['TestClassFactory', 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCallClassNameMethodStatically()
    {
        $container = new Venta\Container\MutableContainer;

        $this->assertInstanceOf(TestClassContract::class, $container->call('StaticTestFactory::create'));
    }

    /**
     * @test
     */
    public function canCallClosure()
    {
        $container = new Venta\Container\MutableContainer;
        $object = new stdClass();
        $object->key = 'value';
        $container->bind(stdClass::class, $object);
        $result = $container->call(function (stdClass $dependency) {
            return $dependency->key;
        });

        $this->assertSame('value', $result);
    }

    /**
     * @test
     */
    public function canCallInterfaceMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassFactoryContract::class, new TestClassFactory(new stdClass()));

        $this->assertInstanceOf(TestClassContract::class, $container->call('TestClassFactoryContract::create'));
    }

    /**
     * @test
     */
    public function canCallInvokableClassName()
    {
        $container = new Venta\Container\MutableContainer;
        $this->assertInstanceOf(TestClassContract::class, $container->call('TestClassFactory'));
    }

    /**
     * @test
     */
    public function canCallInvokableObject()
    {
        $container = new Venta\Container\MutableContainer;
        $invokable = new TestClassFactory(new stdClass());
        $result = $container->call($invokable);

        $this->assertInstanceOf(TestClassContract::class, $result);
    }

    /**
     * @test
     */
    public function canCallObjectMethodFromArrayCallable()
    {
        $container = new Venta\Container\MutableContainer;
        $result = $container->call([new TestClassFactory(new stdClass()), 'createAndSetValue']);

        $this->assertInstanceOf(TestClassContract::class, $result);
        $this->assertInstanceOf(stdClass::class, $result->getValue());
    }

    /**
     * @test
     */
    public function canCheckEntryIsResolvable()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, TestClass::class);

        $this->assertTrue($container->has(TestClassContract::class));
        $this->assertTrue($container->has(stdClass::class));
        $this->assertFalse($container->has('UnknownInterface'));
    }

    /**
     * @test
     */
    public function canDecorateConcreteInstance()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, new TestClass(new stdClass));
        $container->addDecorator(TestClassContract::class, function ($previous) {
            return new class($previous) implements TestClassContract
            {
            };
        });

        $concrete = $container->get(TestClassContract::class);

        $this->assertInstanceOf(TestClassContract::class, $concrete);
        $this->assertNotInstanceOf(TestClass::class, $concrete);
    }

    /**
     * @test
     */
    public function canDecorateFactoryDefinedBinding()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, function () {
            return new TestClass(new stdClass);
        });
        $container->addDecorator(TestClassContract::class, function ($previous) {
            return new class($previous) implements TestClassContract
            {
            };
        });

        $concrete = $container->get(TestClassContract::class);

        $this->assertInstanceOf(TestClassContract::class, $concrete);
        $this->assertNotInstanceOf(TestClass::class, $concrete);
    }

    /**
     * @test
     */
    public function canDecoratePreviousImplementation()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, TestClass::class);
        $container->addDecorator(TestClassContract::class, function ($previous) {
            return new class($previous) implements TestClassContract
            {
            };
        });

        $concrete = $container->get(TestClassContract::class);

        $this->assertInstanceOf(TestClassContract::class, $concrete);
        $this->assertNotInstanceOf(TestClass::class, $concrete);
    }

    /**
     * @test
     */
    public function canDecorateWithClassName()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, TestClass::class);
        $container->addDecorator(TestClassContract::class, TestClassDecorator::class);

        $concrete = $container->get(TestClassContract::class);

        $this->assertInstanceOf(TestClassContract::class, $concrete);
        $this->assertNotInstanceOf(TestClass::class, $concrete);
    }

    /**
     * @test
     */
    public function canResolveClassWithConstructorParameters()
    {
        $container = new Venta\Container\MutableContainer;

        $this->assertInstanceOf(
            'SimpleConstructorParametersClass',
            $container->get('SimpleConstructorParametersClass')
        );
        $this->assertInstanceOf(stdClass::class, $container->get('SimpleConstructorParametersClass')->getItem());
    }

    /**
     * @test
     */
    public function canResolveContractToContractBinding()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, TestClass::class);
        $container->bind(Contract::class, TestClassContract::class);

        $this->assertInstanceOf(TestClass::class, $container->get(Contract::class));
        $this->assertSame($container->get(Contract::class), $container->get(Contract::class));
        $this->assertSame($container->get(TestClassContract::class), $container->get(TestClassContract::class));
        $this->assertSame($container->get(Contract::class), $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromAbstractClassNameStaticMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, 'StaticTestFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassName()
    {
        $container = new Venta\Container\MutableContainer;

        $this->assertInstanceOf(stdClass::class, $container->get('\stdClass'));
        $this->assertInstanceOf(stdClass::class, $container->get('stdClass'));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, 'TestClassFactory::create');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameMethodArray()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, [TestClassFactory::class, 'create']);

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClassNameStaticMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, 'TestClassFactory::staticCreate');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromClosure()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(stdClass::class, function () {
            return new stdClass;
        });

        $this->assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    /**
     * @test
     */
    public function canResolveFromClosureWithArguments()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, function (TestClass $class) {
            return $class;
        });

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromFunctionName()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, 'createTestClass');

        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromInvokableClassName()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassFactory::class, TestClassFactory::class);

        $this->assertInstanceOf(TestClassFactory::class, $container->get(TestClassFactory::class));
    }

    /**
     * @test
     */
    public function canResolveFromInvokableObject()
    {
        $factory = Mockery::mock(TestClassFactory::class)
                          ->shouldReceive('__invoke')
                          ->withNoArgs()
                          ->andReturn(Mockery::mock(TestClassContract::class))
                          ->once()
                          ->getMock();

        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, $factory);
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveFromObjectMethodArray()
    {
        $factory = Mockery::mock(TestClassFactory::class)
                          ->shouldReceive('create')
                          ->withNoArgs()
                          ->andReturn(Mockery::mock(TestClassContract::class))
                          ->once()
                          ->getMock();

        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, [$factory, 'create']);
        $this->assertInstanceOf(TestClassContract::class, $container->get(TestClassContract::class));
    }

    /**
     * @test
     */
    public function canResolveInstanceAsShared()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(\Venta\Contracts\Container\Container::class, $this);

        $this->assertSame($this, $container->get(\Venta\Contracts\Container\Container::class));
        $this->assertSame(
            $container->get(\Venta\Contracts\Container\Container::class),
            $container->get(\Venta\Contracts\Container\Container::class)
        );
    }

    /**
     * @test
     */
    public function canResolveSharedFromClosure()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(stdClass::class, function () {
            return new stdClass;
        }, true);

        $this->assertSame($container->get(stdClass::class), $container->get(stdClass::class));
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksDirectCircularDependency()
    {
        $container = new Venta\Container\MutableContainer;
        $container->get(D::class);
    }

    /**
     * @test
     */
    public function checksIfServiceMethodIsCallable()
    {
        $container = new Venta\Container\MutableContainer;

        $this->assertTrue($container->isCallable('TestClassFactory::create'));
        $this->assertFalse($container->isCallable('TestClassFactoryContract::create'));
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksIndirectCircularDependency()
    {
        $container = new Venta\Container\MutableContainer;
        $container->get(A::class);
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\CircularReferenceException
     */
    public function checksInflectionCircularDependency()
    {
        $container = new Venta\Container\MutableContainer;
        $container->addInflection(E::class, 'setDependency');
        $container->get(E::class);
    }

    /**
     * @test
     * @expectedException \Venta\Container\Exception\UnresolvableDependencyException
     */
    public function throwsContainerExceptionIfCantResolve()
    {
        $container = new Venta\Container\MutableContainer;
        $container->factory(TestClassContract::class, function ($someUnresolvableDependency) {
        });
        $container->get(TestClassContract::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfCallingNotCallable()
    {
        $container = new Venta\Container\MutableContainer;
        $container->call(42);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfEntryClassNameIsInvalid()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassContract::class, 'Some unknown class');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfIdIsInvalid()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind('Some unknown interface', TestClass::class);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionIfInflectionMethodDoesNotExist()
    {
        $container = new Venta\Container\MutableContainer;
        $container->addInflection(TestClass::class, 'unknownMethod');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionOnInvalidCallableCall()
    {
        $container = new Venta\Container\MutableContainer;
        $container->call('SomeInvalidCallableToCall');
    }

    /**
     * @test
     * @expectedException Venta\Container\Exception\UninstantiableServiceException
     */
    public function throwsExceptionWhenBoundToUninstantiableClass()
    {
        $container = new Venta\Container\MutableContainer;
        $container->bind(TestClassFactoryContract::class, StaticTestFactory::class);

        $container->get(TestClassFactoryContract::class);
    }

    /**
     * @test
     * @expectedException Venta\Container\Exception\NotFoundException
     */
    public function throwsExceptionWhenCallsUnresolvableServiceMethod()
    {
        $container = new Venta\Container\MutableContainer;
        $container->call('TestClassFactoryContract::create');
    }

    /**
     * @test
     * @expectedException \Interop\Container\Exception\NotFoundException
     */
    public function throwsNotFoundExceptionIfNotResolvable()
    {
        $container = new Venta\Container\MutableContainer;
        $container->get(TestClassContract::class);
    }
}
