<?php

namespace NeroAzure\Test\Render3d;

use PHPUnit\Framework\TestCase;

class Render3dTestCase extends TestCase
{
	/**
	 * The full path to the Tests/Files/ folder.
	 */
	protected string $testFilesDir;

	/**
	 * The full path to a temporary working directory to use for tests.
	 * 
	 * Note that the working directory starts out not created (so that it can test creating the directory)
	 */
	protected string $workingDir;

	/**
	 * If set to true, will not clean up after itself by removing working dir
	 */
	protected bool $keepWorkingDir = false;


	public function setUp(): void
	{
		$this->workingDir = sys_get_temp_dir() . '/Render3dTests/';
		$this->testFilesDir = dirname(__FILE__) . '/Files/';

		// Make sure the directory did not stick around
		$this->removeWorkingDir();

		parent::setUp();
	}

	public function tearDown(): void
	{
		// Remove any files created
		$this->removeWorkingDir();

		parent::tearDown();
	}

	/**
	 * Removes the currently set working directory and all contents.
	 */
	protected function removeWorkingDir (): void
	{
		if (!$this->keepWorkingDir && $this->workingDir && file_exists($this->workingDir)) {
			array_map('unlink', glob($this->workingDir . '*'));
			rmdir($this->workingDir);
		}
	}
}