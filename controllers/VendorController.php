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

	
	/**
	 * Array of acceptable mime types
	 * @var array
	 */
	protected $_mimetypes = array(
			'js'	=> 'application/javascript',
			'css'	=> 'text/css',
			'png'	=> 'image/png',
			'gif'	=> 'image/gif',
			'jpg'	=> 'image/jpeg'
	);
	
	
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
			
				$filepath = implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR;
			$filename = str_replace(':', '/', current($params));
			$extension = substr($filename, strrpos($filename,'.')+1);
			
			if (! array_key_exists($extension, $this->_mimetypes)) {
				$this->getResponse()->setHttpResponseCode(500)->sendResponse();
				exit();
			}
			
			$path = $filepath . $filename;
			
			if (file_exists($path)) {
				
				$file = file_get_contents($path);
				
				// Apply minification and expires if we are not in dev mode
				if (false) { //Core::$env != Core::ENV_DEV) {
					$file = str_replace(array(' ', "\n", "\t"), '', $file);
				}
				
				$this->getResponse()->setHeader('Content-type', $this->_mimetypes[$extension])->setBody($file);
				$this->getResponse()->sendResponse();
				exit();

			} else {
				
				$this->getResponse()->setHttpResponseCode(404)->sendResponse();
				exit();
			}
	}
}
