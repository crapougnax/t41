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

	
	public function downloadAction()
	{
		if ($this->_getParam('obj')) {
			$response = $this->getResponse();
			$etag = md5($this->_getParam('obj'));
			
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
				$this->_sendResponse(304);
			}

			$media = new MediaObject(base64_decode($this->_getParam('obj')));

			if (! $media->mime) {
				$this->_sendResponse(404);
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
			$this->_sendResponse(404);
		}
	}
	
	
	protected function _sendResponse($code = null)
	{
		if ($code) {
			$this->getResponse()->setHttpResponseCode($code);
		}
		$this->getResponse()->sendResponse();
		exit();		
	}
}
