<?php


use t41\Core;

/**
 * Assets delivery controller
 * 
 * @author
 * @version 
 */

use t41\ObjectModel\MediaObject;

require_once 'Zend/Controller/Action.php';

/**
 * Assets elements loader
 *
 */
class t41_MediasController extends Zend_Controller_Action {

	
	/**
	 * Array of acceptable mime types
	 * @var array
	 */
	protected $_mimetypes = array(
									'js'	=> 'application/javascript', 
									'css'	=> 'text/css', 
									'png'	=> 'image/png', 
									'gif'	=> 'image/gif',
									'jpg'	=> 'image/jpeg',
									'ttf'   =>  'application/octet-stream'
								 );
	
	
	public function downloadAction()
	{
		if ($this->_getParam('obj')) {
			$response = $this->getResponse();
			$etag = md5($this->_getParam('obj'));
			
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
				$response->setHttpResponseCode(304);
				$this->_sendResponse();
			}

			$media = new MediaObject(base64_decode($this->_getParam('obj')));

			if (! $media->mime) {
				$response->setHttpResponseCode(404);
				$this->_sendResponse();
			}
			
			$response->setHeader('Content-Type', $media->mime);
			$response->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', $media->label));
			$response->setHeader('ETag', $etag);
			if (Core::$env == Core::ENV_PROD) {
				$response->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time()+30*86400));
			}
			$response->setBody($media->loadBlob('media'));
			$this->_sendResponse();

		} else {
			$response->setHttpResponseCode(404);
			$this->_sendResponse();
		}
	}
	
	
	protected function _sendResponse()
	{
		$this->getResponse()->sendResponse();
		exit();		
	}
}
