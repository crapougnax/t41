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
			ob_flush();
			$this->_callControllerAction($key, $module, 'hourly');
			print "OK" . $this->cr;
		}
	}
	
	public function dailyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			$this->_callControllerAction($key, $module, 'daily');
			print "OK" . $this->cr;
		}		
	}
	
	public function weeklyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			$this->_callControllerAction($key, $module, 'weekly');
			print "OK" . $this->cr;
		}	
	}
	
	protected function _callControllerAction(string $moduleKey, array $moduleData, string $mode)
	{
	    require_once Core::$basePath . '/application/modules/' . $moduleKey . '/controllers/CronController.php';
	    $controller = sprintf('%s_CronController', $moduleData['controller']['base']);
	    $controller = new $controller($this->_request, $this->_response);
	    $controller->dispatch($mode . 'Action');
	}
}
