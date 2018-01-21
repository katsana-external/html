<?php

namespace Orchestra\Html\TestCase;

use Mockery as m;
use Orchestra\Html\HtmlBuilder;
use PHPUnit\Framework\TestCase;

class HtmlBuilderTest extends TestCase
{
    /**
     * UrlGenerator instance.
     *
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    private $url;

    /**
     * View Factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    private $view;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->url = m::mock('\Illuminate\Contracts\Routing\UrlGenerator');
        $this->view = m::mock('\Illuminate\Contracts\View\Factory');
    }

    /**
     * Teardown the test environment.
     */
    protected function tearDown()
    {
        unset($this->url);
        m::close();
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::create() with content.
     *
     * @test
     */
    public function testCreateWithContent()
    {
        $stub = new HtmlBuilder($this->url, $this->view);
        $expected = '<div class="foo">Bar</div>';
        $output = $stub->create('div', 'Bar', ['class' => 'foo']);

        $this->assertEquals($expected, $output);
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::create() without content.
     *
     * @test
     */
    public function testCreateWithoutContent()
    {
        $stub = new HtmlBuilder($this->url, $this->view);
        $expected = '<img src="hello.jpg" class="foo">';
        $output = $stub->create('img', [
            'src' => 'hello.jpg',
            'class' => 'foo',
        ]);

        $this->assertEquals($expected, $output);

        $expected = '<img src="hello.jpg" class="foo">';
        $output = $stub->create('img', null, [
            'src' => 'hello.jpg',
            'class' => 'foo',
        ]);

        $this->assertEquals($expected, $output);
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::entities() method.
     *
     * @test
     */
    public function testEntitiesMethod()
    {
        $stub = new HtmlBuilder($this->url, $this->view);

        $this->assertEquals('<img src="foo.jpg">', $stub->entities($stub->raw('<img src="foo.jpg">')));

        $this->assertEquals('&lt;img src=&quot;foo.jpg&quot;&gt;', $stub->entities('<img src="foo.jpg">'));
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::raw() method.
     *
     * @test
     */
    public function testRawExpressionMethod()
    {
        $stub = new HtmlBuilder($this->url, $this->view);
        $this->assertInstanceOf('\Illuminate\Contracts\Support\Htmlable', $stub->raw('hello'));
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::decorate() method.
     *
     * @test
     */
    public function testDecorateMethod()
    {
        $stub = new HtmlBuilder($this->url, $this->view);

        $output = $stub->decorate(['class' => 'span4 table'], ['id' => 'foobar']);
        $expected = ['id' => 'foobar', 'class' => 'span4 table'];
        $this->assertEquals($expected, $output);

        $output = $stub->decorate(['class' => 'span4 !span12'], ['class' => 'span12']);
        $expected = ['class' => 'span4'];
        $this->assertEquals($expected, $output);

        $output = $stub->decorate(['id' => 'table'], ['id' => 'foobar', 'class' => 'span4']);
        $expected = ['id' => 'table', 'class' => 'span4'];
        $this->assertEquals($expected, $output);
    }

    /**
     * Test Orchestra\Html\HtmlBuilder methods use HtmlBuilder::raw() and
     * return Orchestra\Support\Expression.
     *
     * @test
     */
    public function testHtmlBuilderMethodsReturnAsExpression()
    {
        $url = $this->url;

        $url->shouldReceive('asset')->once()->with('foo.png', false)->andReturn('foo.png')
            ->shouldReceive('to')->once()->with('foo', m::type('Array'), '')->andReturn('foo');

        $stub = new HtmlBuilder($url, $this->view);
        $stub->macro('foo', function () {
            return 'foo';
        });

        $stub->macro('foobar', function () {
            return new \Illuminate\Support\Fluent();
        });

        $image = $stub->image('foo.png');
        $link = $stub->link('foo');
        $mailto = $stub->mailto('hello@orchestraplatform.com');
        $ul = $stub->ul(['foo' => ['bar' => 'foobar']]);
        $foo = $stub->foo();
        $foobar = $stub->foobar();

        $this->assertInstanceOf('\Illuminate\Contracts\Support\Htmlable', $image);
        $this->assertInstanceOf('\Illuminate\Contracts\Support\Htmlable', $link);
        $this->assertInstanceOf('\Illuminate\Contracts\Support\Htmlable', $mailto);
        $this->assertInstanceOf('\Illuminate\Contracts\Support\Htmlable', $ul);
        $this->assertInstanceOf('\Illuminate\Support\Fluent', $foobar);

        $this->assertEquals('foo', $foo);
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::__call() persist since we're using
     * static for macros (this won't happen before).
     *
     * @test
     */
    public function testMagicCallMethodPersistWhenItShouldnt()
    {
        $this->assertInstanceOf('\Illuminate\Support\Fluent', with(new HtmlBuilder($this->url, $this->view))->foobar());
    }

    /**
     * Test Orchestra\Html\HtmlBuilder::__call() method throws exception.
     *
     * @expectedException \BadMethodCallException
     */
    public function testMagicCallMethodThrowsException()
    {
        with(new HtmlBuilder($this->url, $this->view))->missing();
    }
}
