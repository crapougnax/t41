<?php

namespace t41\Controller;

use t41\Core;
use t41\View;

class AbstractCronController extends \Zend_Controller_Action {

	
	protected $_component;
	
	
	public function preDispatch()
	{
		if (Core::$mode != 'cli') {
			echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
			echo '<body><pre>';
		}
	}
	
	
	public function indexAction()
	{
		if (Core::$env != Core::ENV_DEV) {
			exit("Access denied");
		}
		
		echo '<h1>Available actions</h1>';
		$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action' && substr($method,0,5) != 'index') {
				printf('<a href="/%s/%s/%s">%s</a><br/>'
						, $this->_getParam('module')
						, $this->_getParam('controller')
						, strtolower(str_replace('Action', '', $method))
						, $method
				);
			}
		}
	}
	
	
	public function postDispatch()
	{
		if (Core::$mode != 'cli') {
			echo "</pre></body></html>\n";
		}
	}

	public function hourlyAction()
	{
		return 'SKIPPED';
	}


	public function dailyAction()
	{
		return 'SKIPPED';
	}

	
	public function weeklyAction()
	{
		return 'SKIPPED';
	}
	
	
	protected function _getTS()
	{
		$date = new \DateTime();
		return $date->format('Y-m-d H:i:s');
	}
}
