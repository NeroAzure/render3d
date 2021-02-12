<?php

namespace NeroAzure\Render3d\Convert;

use NeroAzure\Render3d\Render3d;

/**
 * The convert abstract class.  All converters must extend this.
 */
abstract class Convert {
	/**
	 * The Render3d object
	 */
	protected Render3d $Render3d;

	/**
	 * @var array<string>
	 */
	protected array $fwriteBuffers = [];

	/**
	 * Constructor gonna construct.
	 * 
	 * @param \NeroAzure\Render3d\Render3d $render3d 
	 */
	public function __construct(Render3d $render3d)
	{
		$this->Render3d = $render3d;
	}

	/**
	 * Write to a file, using a buffer size, to reduce number of writes to a file.
	 * 
	 * This essentially implements our own file write buffer, as the one built in is not always reliable. Doing this
	 * way shaves time off of writing larger files.
	 * 
	 * To write remaining buffer to the file, or to simply not use buffering, pass in 0 for $bufferSize.
	 * 
	 * @param resource $handle
	 * @param string $contents Contents to write to file
	 * @param string $fn Filename being written, only used as array key to store the buffer
	 * @param int $bufferSize Set to 0 to write any remaining in the buffer to the file
	 * @return void
	 */
	protected function fwriteBuffer($handle, string $contents, string $fn, int $bufferSize = 8000): void
	{
		if (!isset($this->fwriteBuffers[$fn])) {
			if (!$bufferSize) {
				// just write directly to file
				fwrite($handle, $contents);
			} else {
				$this->fwriteBuffers[$fn] = '';
			}
		}

		$this->fwriteBuffers[$fn] .= $contents;

		if (strlen($this->fwriteBuffers[$fn]) <= $bufferSize) {
			return;
		}

		if ($bufferSize) {
			fwrite($handle, $this->fwriteBuffers[$fn], $bufferSize);
			$this->fwriteBuffers[$fn] = substr($this->fwriteBuffers[$fn], $bufferSize);
			return;
		}
		
		fwrite($handle, $this->fwriteBuffers[$fn]);
		$this->fwriteBuffers[$fn] = '';
	}

	/**
	 * Converts the current file.
	 * 
	 * Since converters can have multiple steps, and it is possible to "step through" individual steps, it is up to
	 * each converter to "check" the current file type to make sure it knows how to convert that.
	 * 
	 * If conversion successful, this method should update the Render3d's fileType to match the new file type.
	 * 
	 * @return void
	 */
	abstract public function convert(): void;
}