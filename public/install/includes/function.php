<?php
require_once 'Classes.php';

function phrase($phrase = null)
{
	$classes										= new Classes();
	
	return $classes->phrase($phrase);
}
