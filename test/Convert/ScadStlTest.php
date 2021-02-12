<?php

namespace NeroAzure\Test\Render3d\Convert;

use NeroAzure\Render3d\Render3d;
use	NeroAzure\Render3d\Convert\ScadStl;
use NeroAzure\Test\Render3d\Render3dTestCase;

class ScadStlTest extends Render3dTestCase
{
	public function testConvert(): void
	{
		/** @var \NeroAzure\Render3d\Render3d|\PHPUnit\Framework\MockObject\MockObject $render3d */
		$render3d = $this->getMockBuilder(Render3d::class)
			->onlyMethods(['cmd'])
			->getMock();

		$render3d->expects($this->once())
			->method('cmd')
			->with('openscad -o "example.stl" "example.scad"');

		$render3d->workingDir($this->workingDir);
		$render3d->file('example');
		$render3d->fileType('scad');

		// Must mock up the file being created so that the convert process thinks it was successful
		file_put_contents($this->workingDir . 'example.stl', 'file contents');

		$current = getcwd();
		chdir($this->workingDir);
		$converter = new ScadStl($render3d);

		$converter->convert(false);
		chdir($current);
		// Make sure it updated the file type when successful
		$this->assertEquals('stl', $render3d->fileType(), 'Ensure that the file type was updated when convert successful');
		$this->assertEquals('example', $render3d->file(), 'Ensure that the filename stayed the same for consistency');
	}
}