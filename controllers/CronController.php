<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core,
	t41\ObjectModel,
	t41\ObjectModel\Collection,
	t41\Backend,
	t41\View,
	t41\View\Action;
use t41\Core\Module;

require_once 'Zend/Controller/Action.php';

class t41_CronController extends Zend_Controller_Action {

	
	protected $_modules = array();
	
	
	public $cr = null;
	

	public function preDispatch()
	{
		$this->cr = Core::$mode == 'cli' ? "\n" : '<br/>';
		// get active modules list
		// detect existing CronController()
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
			$this->_forward('hourly','cron',$module['controller']['base']);
			print "OK";
			print $this->cr;
		}
	}
	
	
	public function dailyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			require_once Core::$basePath . '/application/modules/' . $key . '/controllers/CronController.php';
			$controller = sprintf('%s_CronController', $module['controller']['base']);
			$controller = new $controller;
			$controller->dailyAction();
//			$this->_forward('daily','cron',$module['controller']['base']);
			print "OK";
			print $this->cr;
		}		
	}
	
	
	public function weeklyAction()
	{
		foreach ($this->_modules as $key => $module) {
			printf("Executing cron jobs in %s module: ", $key);
			$this->_forward('weekly','cron',$module['controller']['base']);
			print "OK";
			print $this->cr;
		}	
	}
}
