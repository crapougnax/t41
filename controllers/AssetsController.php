<?php

/**
 * Assets delivery controller
 * 
 * @author
 * @version 
 */

use t41\Core,
	t41\View;

require_once 'Zend/Controller/Action.php';

/**
 * Assets elements loader
 *
 */
class t41_AssetsController extends Zend_Controller_Action {

	
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
		//Zend_Debug::dump($this->_getAllParams());
		if ($this->_getParam('action')) {
			
 			$params = array_slice($this->_getAllParams(),3,1);
			if (count($params) == 0) {
				die("incorrect url format, should be /t41/assets/<ns>/<type>/file.<ext>");
			}
			
			$segments = array(
								Core::$t41Path, 
								'assets',
								implode(array_keys($params)),
							 );
			
			$filepath = implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR;
			$filename = str_replace(':', '/', current($params));
			$extension = substr($filename, strrpos($filename,'.')+1);
			
			if (! array_key_exists($extension, $this->_mimetypes)) {
				$this->getResponse()->setHttpResponseCode(500)->sendResponse();
				exit();
			}
			
			$path = $filepath . $filename;
			//die($path);
			
			if (file_exists($path)) {
				
				$file = file_get_contents($path);
				
				// Apply minification and expires if we are not in dev mode
				if (Core::$env != Core::ENV_DEV) {
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
