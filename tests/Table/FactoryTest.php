<?php

namespace Orchestra\Html\Tests\Table;

use Illuminate\Container\Container;
use Mockery as m;
use Orchestra\Html\Table\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test Orchestra\Html\Table\Factory::make() method.
     *
     * @test
     */
    public function testMakeMethod()
    {
        $stub = new Factory($this->getContainer());
        $output = $stub->make(function () {
            //
        });

        $this->assertInstanceOf('\Orchestra\Html\Table\TableBuilder', $output);
    }

    /**
     * Test Orchestra\Html\Table\Factory::of() method.
     *
     * @test
     */
    public function testOfMethod()
    {
        $stub = new Factory($this->getContainer());
        $output = $stub->of('foo', function () {
            //
        });

        $this->assertInstanceOf('\Orchestra\Html\Table\TableBuilder', $output);
    }

    /**
     * Get app container.
     *
     * @return Container
     */
    protected function getContainer()
    {
        $app = new Container();
        $app['request'] = m::mock('\Illuminate\Http\Request');
        $app['translator'] = m::mock('\Illuminate\Translation\Translator');
        $app['view'] = m::mock('\Illuminate\Contracts\View\Factory');

        return $app;
    }
}
