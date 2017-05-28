<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core;
use t41\Core\Module;

class t41_CronController extends Zend_Controller_Action {

	protected $_modules = array();
	
	public $cr = null;
	
	public function preDispatch()
	{
		$this->cr = Core::$mode == 'cli' ? "\n" : '<br/>';
		// get active modules list then detect existing CronController()
		foreach (Module::getConfig() as $vendor => $modules) {
			foreach ($modules as $key => $module) {
				
				// ignore disabled module
				if ($module['enabled'] != true) {
					continue;
				}
				
				// ignore module without controller (rare though)
				if (! isset($module['controller'])) {
					continue;
				}
				
				$path = Core::$basePath . "application/modules/$vendor/$key/controllers/CronController.php";
				if (file_exists($path)) {
					$this->_modules[$vendor . '/' . $key] = $module;
				}
			}
		}
	}
	
	public function hourlyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			$this->forward('hourly', 'cron', $module['controller']['base']);
			print "OK" . $this->cr;
		}
	}
	
	public function dailyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			require_once Core::$basePath . '/application/modules/' . $key . '/controllers/CronController.php';
			$controller = sprintf('%s_CronController', $module['controller']['base']);
			$controller = new $controller($this->_request, $this->_response);
			$controller->dailyAction();
			print "OK" . $this->cr;
		}		
	}
	
	public function weeklyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			$this->forward('weekly','cron',$module['controller']['base']);
			print "OK" . $this->cr;
		}	
	}
}
