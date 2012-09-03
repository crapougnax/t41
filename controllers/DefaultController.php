<?php

use t41\Core\Module;

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

	
	protected $_module;
	
	
	public function init() {
		
		View::setTemplate('default.html');
		
		// get page identifiers (module, controller and action)
		Layout::$module		= $this->_getParam('module');
		Layout::$controller	= $this->_getParam('controller');
		Layout::$action		= $this->_getParam('action');
		
		// provide controller with basic information about the current module
		foreach (Module::getConfig() as $vendor => $modules) {
			
			foreach ($modules as $key => $module) {
				
				if (isset ($module['controller']) && Layout::$module == $module['controller']['base']) {
					$this->_module = 'app/' . $vendor . '/' . $key;
					break;
				}
			}
		}
	}
}
