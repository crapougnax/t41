<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core\Layout,
	t41\ObjectModel\ObjectModel,
	t41\ObjectModel\Collection,
	t41\View;

require_once 'Zend/Controller/Action.php';

abstract class t41_DefaultController extends Zend_Controller_Action {

	
	
	public function init() {
		
		View::setTemplate('default.tpl');
		
		// get page identifiers (module, controller and action)
		Layout::$module		= $this->_getParam('module');
		Layout::$controller	= $this->_getParam('controller');
		Layout::$action		= $this->_getParam('action');
	}
}
