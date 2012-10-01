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
	
	protected $_resource;
	
	
	public function init() {
		
		View::setTemplate('default.html');
		
		// get page identifiers (module, controller and action)
		Layout::$module		= $this->_getParam('module');
		Layout::$controller	= $this->_getParam('controller');
		Layout::$action		= $this->_getParam('action');
		
		// provide controller with basic information about the current module
		foreach (Module::getConfig() as $vendor => $modules) {
			
			foreach ($modules as $key => $module) {
				
				if (isset($module['controller']) && Layout::$module == $module['controller']['base']) {
					$this->_module = 'app/' . $vendor . '/' . $key;

					$resource = Layout::$controller;
					if (Layout::$action) $resource .= '/' . Layout::$action;
					//Zend_Debug::dump($module);
					if (isset($module['controller']['items'])) {
						foreach ($module['controller']['items'] as $controller) {
							if ($this->_getCurrentItem($resource, $controller) == true) break;
						}
					}
					if (isset($module['controllers_extends'])) {
						foreach ($module['controllers_extends'] as $controller) {
							if ($this->_getCurrentItem($resource, $controller['items']) == true) break;
						}
					}
					break;
				}
			}
		}
	}
	
	protected function _getCurrentItem($resource,$items)
	{
		//Zend_Debug::dump($items);
		foreach ($items as $key => $item) {
		
			if ($key == $resource) {
				$this->_resource = $item['label'];
				return true;
			}
			
			if (isset($item['items'])) {
				return $this->_getCurrentItem($resource, $item['items']);
			}
		}
		return false;
	}
}
