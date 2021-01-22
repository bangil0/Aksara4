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
		$error										= false;
		
		/**
		 * Clean visitor log garbage that exceed 7 days
		 */
		helper('filesystem');
		
		$logs										= directory_map(WRITEPATH . 'logs');
		$logs_cleaned								= 0;
		
		if($logs && is_writable(WRITEPATH . 'logs'))
		{
			foreach($logs as $key => $val)
			{
				$modified_time						= filemtime(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . $val);
				
				if('index.html' == $val || !is_file(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . $val) || !$modified_time || $modified_time > strtotime('-1 week'))
				{
					continue;
				}
				
				if(unlink(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . $val))
				{
					$logs_cleaned++;
				}
			}
		}
		else
		{
			$error									= phrase('the_log_path_is_not_writable');
		}
		
		/**
		 * Clean session garbage
		 */
		$session_driver								= service('request')->config->sessionDriver;
		$session_name								= service('request')->config->sessionCookieName;
		$session_expiration							= service('request')->config->sessionExpiration;
		$session_path								= service('request')->config->sessionSavePath;
		$session_match_ip							= service('request')->config->sessionMatchIP;
		$session_cleaned							= 0;
		
		$pattern									= sprintf('#\A%s' . ($session_match_ip ? '[0-9a-f]{32}' : '') . '\z#', preg_quote($session_name));
		
		if(stripos($session_driver, 'file') !== false)
		{
			// file session handler
			if(is_writable(WRITEPATH . $session_path))
			{
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
						
						if(unlink(WRITEPATH . $session_path . DIRECTORY_SEPARATOR . $val))
						{
							$session_cleaned++;
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
			
			$session_cleaned						= $this->model->affected_rows();
		}
		
		if($error)
		{
			// throw with error
			return throw_exception(403, $error, go_to());
		}
		elseif($logs_cleaned || $session_cleaned)
		{
			// throw with amount of cleaned garbage
			$html									= '
				<div class="text-center">
					<i class="mdi mdi-delete-empty mdi-5x text-success"></i>
					<br />
					<h5>
						' . phrase('garbage_cleaned_successfully') . '
					</h5>
				</div>
				<p class="text-center">
					' . phrase('below_is_the_detailed_information_of_cleaned_garbage') . '
				</p>
				<div class="row">
					<div class="col-6 text-right">
						' . phrase('visitor_logs') . '
					</div>
					<div class="col-6">
						' . number_format($logs_cleaned) . ' ' . phrase('cleaned') . '
					</div>
				</div>
				<div class="row">
					<div class="col-6 text-right">
						' . phrase('expired_session') . '
					</div>
					<div class="col-6">
						' . number_format($session_cleaned) . ' ' . phrase('cleaned') . '
					</div>
				</div>
				<hr class="row" />
				<div class="text-right">
					<a href="javascript:void(0)" class="btn btn-light" data-dismiss="modal">
						<i class="mdi mdi-window-close"></i>
						' . phrase('close') . '
						<em class="text-sm">(esc)</em>
					</a>
				</div>
			';
			
			return make_json
			(
				array
				(
					'status'						=> 206,
					'exception'						=> array
					(
						'title'						=> phrase('garbage_cleaned'),
						'icon'						=> 'mdi mdi-check',
						'html'						=> $html
					)
				)
			);
		}
		
		// no garbage found
		return throw_exception(301, phrase('there_is_no_garbage_session_available_in_this_time'), go_to());
	}
}
