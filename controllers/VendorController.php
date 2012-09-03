<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core;

require_once 'AssetsController.php';

class t41_VendorController extends t41_AssetsController {

	
	public function __call($methodName, $args)
	{
		$params = array_slice($this->_getAllParams(),3,1);

		$segments = array(
				Core::$t41Path,
				'vendor',
				$this->_getParam('action'),
				implode(array_keys($params)),
		);
			
		$filepath = implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR;
		$filename = current($params);
			
		//die($filepath . $filename);
		
		if (file_exists($filepath . $filename)) {
			echo file_get_contents($filepath . $filename);
			exit();
		} else {
			$this->getResponse()->setHttpResponseCode(404)->sendResponse();
			exit();
		}
	}
}
