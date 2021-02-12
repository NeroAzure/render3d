<?php

namespace NeroAzure\Render3d\Render;

use NeroAzure\Render3d\Render3d;

/**
 * The render abstract class. All renderers must extend this.
 */
abstract class Render {
	/**
	 * The Render3d object
	 */
	protected Render3d $Render3d;

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
	 * Renders the current file.
	 * 
	 * This can do some automated file conversions by calling convertTo() to get the file type in the accepted format
	 * for this rendering. This is the only time fileType should change (if changed by a convert), the fileType should
	 * not be changed to match the rendered image's file type.
	 * 
	 * @return string The full path to the rendered image
	 * @throws \Exception throws exception if there are problems rendering the image
	 */
	abstract public function render(): string;
}