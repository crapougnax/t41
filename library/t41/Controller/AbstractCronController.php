<?php

namespace t41\Controller;

use t41\Core;

class AbstractCronController extends \Zend_Controller_Action {

	
	public function preDispatch()
	{
		if (Core::$mode != 'cli') {
			echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
			echo '<body><pre>';
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
