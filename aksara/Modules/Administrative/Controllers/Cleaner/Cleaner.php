<?php namespace Aksara\Modules\Administrative\Controllers\Cleaner;
/**
 * Administrative > Cleaner
 * This module is used to clean everything that not used anymore by
 * the application (garbage).
 *
 * @author			Aby Dahana
 * @profile			abydahana.github.io
 * @website			www.aksaracms.com
 * @since			version 4.0.0
 * @copyright		(c) 2021 - Aksara Laboratory
 */
class Cleaner extends \Aksara\Laboratory\Core
{
	public function __construct()
	{
		parent::__construct();
		
		$this->restrict_on_demo();
		
		$this->set_permission(1);
		
		$this->set_theme('backend');
	}
	
	public function index()
	{
		$this->set_title(phrase('garbage_session_cleaner'))
		->set_icon('mdi mdi-trash-can')
		
		->render();
	}
	
	/**
	 * Clean unused session
	 */
	public function clean()
	{
		$session_driver								= service('request')->config->sessionDriver;
		$session_name								= service('request')->config->sessionCookieName;
		$session_expiration							= service('request')->config->sessionExpiration;
		$session_path								= service('request')->config->sessionSavePath;
		$session_match_ip							= service('request')->config->sessionMatchIP;
		
		$pattern									= sprintf('#\A%s' . ($session_match_ip ? '[0-9a-f]{32}' : '') . '\z#', preg_quote($session_name));
		$affected_data								= 0;
		$error										= false;
		
		if(stripos($session_driver, 'file') !== false)
		{
			// file session handler
			if(is_writable(WRITEPATH . $session_path))
			{
				helper('filesystem');
				
				$session							= directory_map(WRITEPATH . $session_path);
				
				if($session)
				{
					foreach($session as $key => $val)
					{
						$modified_time				= filemtime(WRITEPATH . $session_path . DIRECTORY_SEPARATOR . $val);
						
						if('index.html' == $val || !preg_match($pattern, $val) || !is_file(WRITEPATH . $session_path . DIRECTORY_SEPARATOR . $val) || !$modified_time || $modified_time > (time() - $session_expiration))
						{
							continue;
						}
						
						if(delete_files(WRITEPATH . $session_path . DIRECTORY_SEPARATOR . $val))
						{
							$affected_data++;
						}
					}
				}
			}
			else
			{
				$error								= phrase('the_session_save_path_is_not_writable');
			}
		}
		elseif(stripos($session_driver, 'database') !== false)
		{
			// database session handler
			$query									= $this->model->delete
			(
				$session_path,
				array
				(
					'timestamp < '					=> (time() - $session_expiration)
				)
			);
			
			$affected_data							= $this->model->affected_rows();
		}
		
		if($error)
		{
			// throw with error
			return throw_exception(403, $error, go_to());
		}
		elseif($affected_data)
		{
			// throw with amount of cleaned garbage
			return throw_exception(301, '<b>' . number_format($affected_data) . '</b> ' . phrase('garbage_session_has_been_cleaned_successfully'), go_to());
		}
		
		// no garbage found
		return throw_exception(301, phrase('there_is_no_garbage_session_available_in_this_time'), go_to());
	}
}
