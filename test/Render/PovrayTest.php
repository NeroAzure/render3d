<?php

namespace NeroAzure\Test\Render3d\Render;

use Exception;
use NeroAzure\Render3d\Render3d;
use NeroAzure\Render3d\Render\Povray;
use NeroAzure\Test\Render3d\Render3dTestCase;

class PovrayTest extends Render3dTestCase
{
	/**
	 * Tests that render process runs all of the conversion needed to go all the way from scad to pov file type.
	 * @skip
	 */
	public function testRenderConverts(): void
	{
		$this->markTestSkipped('Deprecated methods need to be removed, also test fails');
		/** @var \NeroAzure\Render3d\Render3d|\PHPUnit\Framework\MockObject\MockObject $render3d */
		$render3d = $this->getMockBuilder(Render3d::class)
			->onlyMethods(['cmd', 'convertTo'])
			->getMock();

		$render3d->expects($this->at(0))
			->method('convertTo')
			->with('stl')
			->will($this->returnCallback(fn () => $render3d->fileType('stl')));

		$render3d->expects($this->at(1))
			->method('convertTo')
			->with('pov')
			->will($this->returnCallback(fn () => $render3d->fileType('pov')));

		$render3d->workingDir($this->workingDir);
		$render3d->fileType('scad');
		$render3d->file('example');

		// Must mock up the file being created so that the render process thinks it was successful
		file_put_contents($this->workingDir . 'example.png', 'file contents');

		$render = new Povray($render3d);
		$render->render();
	}

	/**
	 * Makes sure render returns the path
	 */
	public function testRenderReturn(): void
	{
		/** @var \NeroAzure\Render3d\Render3d|\PHPUnit\Framework\MockObject\MockObject $render3d */
		$render3d = $this->getMockBuilder(Render3d::class)
			->onlyMethods(['cmd', 'convertTo'])
			->getMock();

		$render3d->expects($this->never())
			->method('convertTo');

		$render3d->expects($this->once())
			->method('cmd');

		$render3d->workingDir($this->workingDir);
		$render3d->fileType('pov');
		$render3d->file('example');

		// Must mock up the file being created so that the render process thinks it was successful
		file_put_contents($this->workingDir . 'example.png', 'file contents');

		$render = new Povray($render3d);
		$path = $render->render();
		$this->assertEquals($this->workingDir . 'example.png', $path);
	}

	/**
	 * Test that exception is thrown when render seems to fail
	 */
	public function testRenderFail(): void
	{
		/** @var \NeroAzure\Render3d\Render3d|\PHPUnit\Framework\MockObject\MockObject $render3d */
		$render3d = $this->getMockBuilder(Render3d::class)
			->onlyMethods(['cmd', 'convertTo'])
			->getMock();

		$render3d->expects($this->never())
			->method('convertTo');

		$render3d->expects($this->once())
			->method('cmd');

		$render3d->workingDir($this->workingDir);
		$render3d->fileType('pov');
		$render3d->file('example');

		// do NOT write contents to file...  so it should throw an exception when it sees that file does not exist

		$render = new Povray($render3d);
		$this->expectException(Exception::class);
		$render->render();
	}
}