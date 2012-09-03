<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core;

require_once 'AssetsController.php';

class t41_AppController extends t41_AssetsController {

	
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
		if ($this->_getParam('action')) {
			
			$params = array_slice($this->_getAllParams(),3,1);
			if (count($params) == 0) {
				die("incorrect url format");
			}
			
			$segments = array(
								Core::$basePath, 
								'application',
								'modules', 
								$this->_getParam('action'), 
								implode(array_keys($params)),
								'assets',
							 );
			
			$filepath = implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR;
			$filename = str_replace(':', '/', current($params));
			$extension = substr($filename, strrpos($filename,'.')+1);
			
			$path = $filepath . $extension . DIRECTORY_SEPARATOR . $filename;
			//die($path);
			
			if (file_exists($path)) {
				$file = file_get_contents($path);
				
				// Apply minification and expires if we are not in dev mode
				if (false) {
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
}
