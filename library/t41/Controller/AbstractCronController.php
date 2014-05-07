<?php

namespace t41\Controller;

class AbstractCronController extends \Zend_Controller_Action {


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
}
