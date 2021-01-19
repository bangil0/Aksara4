<?php
class Classes
{
	public function __construct()
	{
		if(isset($_SESSION['language']) && in_array($_SESSION['language'], array('en', 'id')))
		{
			$language								= $_SESSION['language'];
		}
		else
		{
			$language								= 'en';
		}
		
		$this->phrase								= include dirname(__DIR__) . '/languages/' . $language . '.php';
	}
	
	public function phrase($phrase = null)
	{
		if(isset($this->phrase[$phrase]))
		{
			return $this->phrase[$phrase];
		}
		
		return ucwords(str_replace('_', ' ', $phrase));
	}
}
