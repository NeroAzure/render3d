<?php

namespace Libre3d\Test\Render3d;

use Exception;
use Libre3d\Render3d\Convert\Convert;
use \Libre3d\Render3d\Render3d;
use Libre3d\Render3d\Render\Render;

class Render3dTest extends Render3dTestCase
{
	protected Render3d $render3d;

	public function setUp(): void
	{
		$this->render3d = new Render3d();

		parent::setUp();
	}

	public function testWorkingDir(): void
	{
		$this->assertFalse(file_exists($this->workingDir));

		$this->render3d->workingDir($this->workingDir);

		$this->assertTrue(file_exists($this->workingDir));
		// Make sure it is retained
		$this->assertEquals($this->workingDir, $this->render3d->workingDir());
	}

	public function testFilename(): void
	{
		// Test normal filename
		$filename = $this->render3d->filename('filename.ext');
		$this->assertEquals('filename.ext', $filename);
		$this->assertEquals('filename', $this->render3d->file());
		$this->assertEquals('ext', $this->render3d->fileType());

		$this->render3d->workingDir($this->workingDir);

		$this->assertFileDoesNotExist($this->workingDir . 'example.scad');

		$filename = $this->render3d->filename($this->testFilesDir . 'example.scad');

		// Make sure it "copied" the file
		$this->assertFileExists($this->workingDir . 'example.scad');
		$this->assertFileEquals($this->testFilesDir . 'example.scad', $this->workingDir . 'example.scad');

		// Make sure params got set correctly
		$this->assertEquals('example.scad', $filename);
		$this->assertEquals('example', $this->render3d->file());
		$this->assertEquals('scad', $this->render3d->fileType());
	}

	/**
	 * Make sure it throws exception if filename called with full path and working DIR is not set
	 */
	public function testFilenameWorkingdirException(): void
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Working directory required');

		// Just call filename without calling workingDir first
		$this->render3d->filename($this->testFilesDir.'example.scad');
	}

	public function testFile(): void
	{
		// Without seeing anything, file should be empty
		$this->assertEmpty($this->render3d->file());
		$this->assertEquals('file1', $this->render3d->file('file1'));
		// make sure it retains value
		$this->assertEquals('file1', $this->render3d->file());

		$this->render3d->file('file2');

		$this->assertEquals('file2', $this->render3d->file());
	}

	public function testFileType(): void
	{
		// Without seeing anything, it should be empty
		$this->assertEmpty($this->render3d->fileType());
		$this->assertEquals('ext1', $this->render3d->fileType('ext1'));
		// make sure it retains value
		$this->assertEquals('ext1', $this->render3d->fileType());

		$this->render3d->fileType('ext2');

		$this->assertEquals('ext2', $this->render3d->fileType());
	}

	public function testDirMask(): void
	{
		// Without seeing anything, it should be empty
		$this->assertEquals(0755, $this->render3d->dirMask());
		$this->assertEquals(0765, $this->render3d->dirMask(0765));
		// make sure it retains value
		$this->assertEquals(0765, $this->render3d->dirMask());

		$this->render3d->dirMask(0111);

		$this->assertEquals(0111, $this->render3d->dirMask());
	}

	public function testExecutable(): void
	{
		// Without seeing anything, it should return command
		$this->assertEquals('exe1', $this->render3d->executable('exe1'));
		$this->assertEquals('/bin/exe1', $this->render3d->executable('exe1', '/bin/exe1'));
		// make sure it retains value
		$this->assertEquals('/bin/exe1', $this->render3d->executable('exe1'));

		$this->render3d->executable('exe2', '/usr/bin/exe2');

		$this->assertEquals('/usr/bin/exe2', $this->render3d->executable('exe2'));
	}

	public function testRender(): void
	{
		$render = $this->getMockBuilder(Render::class)
			->disableOriginalConstructor()
			->getMock();

		$render->expects($this->once())
			->method('render');

		$this->render3d->registerRenderer($render, 'engine');

		$this->render3d->fileType('from-type');
		$this->render3d->workingDir($this->workingDir);

		$this->render3d->render('engine');
	}

	public function testConvertTo(): void
	{
		$converter = $this->getMockBuilder(Convert::class)
			->disableOriginalConstructor()
			->getMock();

		$converter
			->expects($this->once())
			->method('convert');

		$this->render3d->registerConverter($converter, 'from-type', 'to-type');

		$this->render3d->fileType('from-type');
		$this->render3d->workingDir('/tmp/testDir/');

		$this->render3d->convertTo('to-type');
	}

	public function testCmdBuffer(): void
	{
		$msg = 'AYB';
		$this->render3d->workingDir($this->workingDir);
		$this->render3d->filename('file.from');

		$converter = $this->getMockBuilder(Convert::class)
			->disableOriginalConstructor()
			->getMock();

		$converter->expects($this->any())
			->method('convert')
			->will($this->returnCallback(function () use ($msg) {
				$this->render3d->cmd('echo '.$msg);
			}));

		$this->render3d->registerConverter($converter, 'from', 'to');

		// starting out, buffer should be empty
		$this->assertEmpty($this->render3d->getBufferAndClean());

		// default should have buffer off, so message should just go away
		ob_start();
		$this->render3d->convertTo('to');
		$this->assertEmpty($this->render3d->getBufferAndClean());
		$this->assertEmpty(ob_get_clean());

		// it should echo out.  We'll catch it in our own buffer
		ob_start();
		$this->render3d->convertTo('to', ['buffer' => Render3d::BUFFER_STD_OUT]);
		$this->assertEmpty($this->render3d->getBufferAndClean());
		$this->assertSame($msg, trim(ob_get_clean()));

		// it should be caught in the buffer
		ob_start();
		$this->render3d->convertTo('to', ['buffer' => Render3d::BUFFER_ON]);
		$this->assertSame($msg, trim($this->render3d->getBufferAndClean()));
		$this->assertEmpty(ob_get_clean());
	}

	public function testOptions(): void
	{
		$defaultOptions = $this->render3d->options();

		$this->render3d->options(['param1' => 'val1']);
		$this->assertSame(array_merge($defaultOptions, ['param1'=>'val1']), $this->render3d->options());
	}
}