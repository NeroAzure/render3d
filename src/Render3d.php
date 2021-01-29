<?php

namespace Libre3d\Render3d;

use Exception;
use Libre3d\Render3d\Convert\Convert;
use Libre3d\Render3d\Render\Render;

/**
 * @todo Use custom Exceptions
 */
class Render3d {
	/**
	 * Turn the buffer off, anything "output" will not be displayed and will not be buffered.
	 * 
	 * This is the default value if nothing is set for "buffer" in the options.
	 * 
	 * Set an option with the key of "buffer" and this as the value to take effect.
	 */
	public const BUFFER_OFF = 'off';

	/**
	 * Does not buffer, lets any output echo straight to standard out.
	 * 
	 * Set an option with the key of "buffer" and this as the value to take effect.
	 */
	public const BUFFER_STD_OUT = 'std_out';

	/**
	 * Turn buffering on so that you can get the contents of what would normally be output to the standard out.
	 * 
	 * Can then get the buffer contents using the method getBufferAndClean.
	 * 
	 * Set an option with the key of "buffer" and this as the value to take effect.
	 */
	public const BUFFER_ON = 'on';

	/**
	 * Array of Renderer objects.
	 * 
	 * @var array<\Libre3d\Render3d\Render\Render>
	 */
	protected array $renderers;

	/**
	 * Array of converter objects.
	 * 
	 * @var array<array<\Libre3d\Render3d\Convert\Convert>>
	 */
	protected array $converters;

	/**
	 * Array of executables, key is the executable and value is what to use on the CMD (usually the full path to the executable).
	 * 
	 * @var array<string>
	 */
	protected array $executables;

	/**
	 * The absolute path to the working directory, where all the files are being worked on.
	 */
	protected string $workingDir;

	/**
	 * The absolute path to the Render3d library.  Used internally when loading things like static files.
	 */
	protected string $render3dDir;

	/**
	 * The base filename, minus the extension.
	 * 
	 * All converted/rendered files will share same base name, just with different extensions that coorespond to the file type.
	 */
	protected string $file = '';

	/**
	 * The file type (file extension).
	 */
	protected string $fileType = '';

	/**
	 * The directory mask to use when creating new directories.
	 */
	protected int $dirMask = 0755;

	/**
	 * Parameters set and used by converters or renderers.
	 * 
	 * @var array<string>
	 */
	protected array $options = [
		'buffer' => 'off'
	];

	protected string $buffer = '';

	/**
	 * Constructor gonna construct
	 */
	public function __construct()
	{
		// Initialize all the things
		$this->render3dDir = dirname(__FILE__) . '/';
	}

	/**
	 * Get and/or set the current working DIR.
	 * 
	 * @param string|null $workingDir If passed, will set the current working DIR.  Must be the absolute path.
	 * @return string Returns the currently set working directory
	 */
	public function workingDir (?string $workingDir = null): string
	{
		if (!empty($workingDir)) {
			$this->workingDir = rtrim($workingDir, '/') . '/';

			if (!file_exists($this->workingDir)) {
				mkdir($this->workingDir, $this->dirMask, true);
			}
		}

		return $this->workingDir;
	}

	/**
	 * Get the directory for the scene directory for the Render3d library.
	 * 
	 * This cannot be changed and is set automatically by the library.
	 * 
	 * @return string
	 */
	public function sceneDir(): string
	{
		return $this->render3dDir . 'Scenes/';
	}

	/**
	 * Get and/or set the relative filename of the current file being worked on.
	 * 
	 * Once a file has been converted or rendered, this will change to the converted or rendered filename.
	 * 
	 * If you pass in a filename, it will set the "file" and "file type" based on the base name and extension.  If it
	 * includes a path (can even be a URL if the PHP environment is configured to allow this), and
	 * the workingDir is already set, it will automatically copy in the file from the specified path into the currently
	 * set working directory as well, or throw an exception if the working directory is not set.
	 * 
	 * @param string|null $filename If set, will try to set file and filetype accordingly, and possibly copy the file into
	 *   the working directory.
	 * @return string The current filename, relative to the working directory
	 * @throws \Exception
	 */
	public function filename(?string $filename = null): string
	{
		if (!empty($filename)) {
			$pathInfo = pathinfo($filename);
			if (!empty($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' && file_exists($filename)) {
				if (empty($this->workingDir)) {
					// Set the working dir based on the file path
					throw new Exception('Working directory required.');
				}

				// Copy it into the working folder
				$copyResult = copy($filename, $this->workingDir . $pathInfo['basename']);

				if (!$copyResult) {
					throw new Exception('Copying file to working directory failed.');
				}
			}

			// NOTE: Filename will be minus the extension
			$this->file = $pathInfo['filename'];
			$this->fileType = $pathInfo['extension'] ?? '';
		}

		return $this->file . '.' . $this->fileType;
	}

	/**
	 * Get and/or set the current base name used for all files being processed.
	 * 
	 * @param string|null $file If passed in, will set the file to the value.  Cannot include path info.
	 * @return string The current value for the base filename (without the extension).
	 */
	public function file (?string $file = null): string
	{
		if (!empty($file)) {
			$this->file = $file;
		}

		return $this->file;
	}

	/**
	 * Get and/or set the current file type.
	 * 
	 * In Render3d library, "fileType" is always synonymous with the file extension used.
	 * 
	 * @param string|null $fileType The file type, without a leading '.'.
	 * @return string the current file type (file extension) without the dot.
	 */
	public function fileType(?string $fileType = null): string
	{
		if (!empty($fileType)) {
			$this->fileType = $fileType;
		}

		return $this->fileType;
	}

	/**
	 * Get and/or set the current directory mask used when creating a new directory.
	 * 
	 * @param int|null $dirMask
	 * @return int
	 */
	public function dirMask(?int $dirMask = null): int
	{
		if (!empty($dirMask)) {
			$this->dirMask = $dirMask;
		}

		return $this->dirMask;
	}

	/**
	 * Gets/Sets the location to use for the given command.
	 * 
	 * If using as a getter (by not passing in $use), and that executable has not previously been set, it returns
	 * the $executable value.
	 * 
	 * @param string $executable The executable name, e.g. "ls".
	 * @param string|null $use What to use on the command line for that executable, e.g. "/bin/ls".
	 * @return string What to use for the executable.
	 */
	public function executable(string $executable, ?string $use = null): string
	{
		if (!empty($use)) {
			$this->executables[$executable] = $use;
		}

		return $this->executables[$executable]
			?? $executable;
	}

	/**
	 * Convert from the current file type to the one given.
	 * 
	 * @param string $fileType The "end" file type to convert to.
	 * @param array<mixed>|null $options Array of options for the specific conversion.
	 * @return void
	 * @throws \Exception
	 */
	public function convertTo (string $fileType, ?array $options = null): void
	{
		if (empty($this->fileType) || empty($this->workingDir)) {
			// File type or working dir not set.
			throw new Exception('Filetype and working dir are not set.');
		}

		// Set the options
		if (!empty($options)) {
			$this->options($options);
		}

		$this->startBuffer();

		$currentDir = getcwd();
		
		//we need to be in base directory for all the rendering stuff to work...
		chdir($this->workingDir);

		$converter = $this->getConverter($this->fileType, $fileType);

		try {
			$converter->convert();
		} catch (Exception $e) {
			chdir($currentDir);
			$this->stopBuffer();

			throw $e;
		}
		
		// Now go back to the starting dir.
		chdir($currentDir);
		$this->stopBuffer();
	}

	/**
	 * Render the file and return the full path to the rendered image.
	 * 
	 * @param string $engine
	 * @param array<mixed>|null $options
	 * @return string Full path to rendered image file
	 * @throws \Exception
	 */
	public function render (string $engine = 'povray', ?array $options = null) {
		if (empty($this->fileType) || empty($this->workingDir)) {
			// File type or working dir not set
			throw new Exception('Filetype and Workingdir must be set first.');
		}
		
		// Set the options
		if (!empty($options)) {
			$this->options($options);
		}
		
		$this->startBuffer();

		$currentDir = getcwd();
		
		//we need to be in base directory for all the rendering stuff to work...
		chdir($this->workingDir);

		$renderer = $this->getRenderer($engine);
		try {
			$result = $renderer->render();
		} catch (Exception $e) {
			chdir($currentDir);
			$this->stopBuffer();
			throw $e;
		}
		
		// Now go back to the starting dir
		chdir($currentDir);

		$this->stopBuffer();
		return $result;
	}

	/**
	 * Get converter to convert from the "from" to the "to".
	 * 
	 * @param string $fromType
	 * @param string $toType
	 * @return \Libre3d\Render3d\Convert\Convert
	 */
	public function getConverter(string $fromType, string $toType): Convert
	{
		if (!isset($this->converters[$fromType][$toType])) {
			$class = 'Libre3d\Render3d\Convert\\'.ucfirst($fromType).ucfirst($toType);
			$this->registerConverter($class, $fromType, $toType);
		}

		return $this->converters[$fromType][$toType];
	}

	/**
	 * Get renderer object for the given render engine.
	 * 
	 * @param string $engine 
	 * @return \Libre3d\Render3d\Render\Render
	 */
	public function getRenderer(string $engine): Render
	{
		if (!isset($this->renderers[$engine])) {
			$class = 'Libre3d\Render3d\Render\\'.ucfirst($engine);
			$this->registerRenderer($class, $engine);
		}

		return $this->renderers[$engine];
	}

	/**
	 * Register a new converter object, possibly over-writing any previously set converters for the given from and to.
	 * 
	 * @param string|\Libre3d\Render3d\Convert\Convert $class
	 * @param string $fromType
	 * @param string $toType
	 * @return void
	 * @throws \Exception
	 */
	public function registerConverter($class, string $fromType, string $toType): void
	{
		if (is_string($class)) {
			if (!class_exists($class)) {
				throw new Exception('Class ('.$class.') does not exist.');
			}

			$class = new $class($this);
		}

		if (!is_subclass_of($class, Convert::class)) {
			throw new Exception('Must be a sub-class of type Libre3d\Render3d\Convert\Convert.');
		}

		$this->converters[$fromType][$toType] = $class;
	}

	/**
	 * Register a rendering engine.
	 * 
	 * @param string|\Libre3d\Render3d\Render\Render $class
	 * @param string $engine
	 * @return void
	 */
	public function registerRenderer($class, string $engine): void
	{
		if (is_string($class)) {
			if (!class_exists($class)) {
				throw new Exception('Class ('.$class.') does not exist.');
			}

			$class = new $class($this);
		}

		if (!is_subclass_of($class, Render::class)) {
			throw new Exception('Must be a sub-class of type Libre3d\Render3d\Render\Render.');
		}

		$this->renderers[$engine] = $class;
	}

	/**
	 * Simple wrapper for running commands on command line, used by individual converters and renderers.
	 * 
	 * Useful to easily log results to file, also useful for test mocking.
	 * 
	 * @param string $call
	 * @return string|bool
	 */
	public function cmd (string $call)
	{
		system ($call . " 2> {$this->workingDir}last_error.txt", $result);
		
		$errContents = trim(file_get_contents("{$this->workingDir}last_error.txt"));

		if (strlen($errContents)) {
			//print it red so it's noticed...  Note that this is wrapped in a buffer.
			echo "<span style='color: red;'>$errContents</span>\n";
		}

		return $result;
	}

	/**
	 * Get or set the options used by converters or renderers.
	 * 
	 * @param array<mixed>|null $options
	 * @return array<mixed>
	 */
	public function options(?array $options = null): array
	{
		if (!empty($options) && is_array($options)) {
			if (!empty($this->options)) {
				$options = array_merge($this->options, $options);
			}

			$this->options = $options;
		}

		return $this->options;
	}

	/**
	 * Get the buffer contents and clean the buffer, similar to ob_get_clean().
	 * 
	 * Note that this requires an "option" to be set with key of "buffer" and value of one of the applicable
	 * BUFFER_ constants on this class.
	 * 
	 * @return string
	 */
	public function getBufferAndClean(): string
	{
		$return = $this->buffer;
		$this->buffer = '';

		return $return;
	}

	/**
	 * Start the buffer, according to option set for "buffer".
	 * 
	 * @return void
	 */
	protected function startBuffer(): void
	{
		if ($this->options['buffer'] !== self::BUFFER_STD_OUT) {
			ob_start();
		}
	}

	/**
	 * Stop the buffer and do what is needed, according to option set for "buffer".
	 * 
	 * @return void
	 */
	protected function stopBuffer(): void
	{
		switch ($this->options['buffer']) {
			case static::BUFFER_ON:
				$this->buffer .= "\n" . ob_get_clean();
				break;

			case static::BUFFER_STD_OUT:
				// Nothing to do, output sent directly to std out.
				break;

			case static::BUFFER_OFF:
				// Fall through to default.
			default:
				ob_end_clean();
				break;
		}
	}
}