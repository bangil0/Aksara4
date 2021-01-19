<?php
/**
 * Helper file
 *
 * @author			Aby Dahana
 * @profile			abydahana.github.io
 * @website			www.aksaracms.com
 * @copyright		(c) 2021 - Aksara Laboratory
 * @since			version 4.1.19
 */
require_once 'Classes.php';

if(!function_exists('phrase'))
{
	/**
	 * Getting the phrase of translation
	 */
	function phrase($phrase = null)
	{
		// load the classes
		$classes									= new Classes();
		
		return $classes->phrase($phrase);
	}
}
