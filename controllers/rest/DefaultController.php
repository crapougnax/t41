<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core\Registry;
use t41\Core;
use t41\ObjectModel;
use t41\Backend;
use t41\View\Action;

require_once 'Zend/Controller/Action.php';

class Rest_DefaultController extends Zend_Controller_Action {

	protected $_uuid;
	
	protected $_obj = null;
	
	protected $_status = 'OK';
	
	protected $_refreshCache = false;
	
	protected $_data = [];
	
	protected $_context = [];

	protected $_actions = ['pre' => [], 'ok' => [], 'nok' => []];
	
	protected $_redirects = [];
	
	protected $_post;
	
	public function init()
	{
		/* redeclare with your ACL in your own controller */
	}
	
	public function preDispatch()
	{
		$this->_post = $this->getRequest()->getPost();

		$this->_uuid = $this->_getParam('uuid');
	
		if ($this->_uuid) {
		    $this->_obj = Core\Registry::get($this->_uuid);
			$this->_defineRedirect(['ok','nok','err']);
	
			if ($this->_obj instanceof ObjectModel\ObjectUri) {
				$this->_obj = ObjectModel::factory($this->_obj);
			}
	
			if (! $this->_obj) {
				$this->_context['message'] = "Unable to restore object";
				$this->_status = 'ERR';
				$this->postDispatch();
			}

			// @todo post actions coming from js, unsecure, we should keep a reference of the handling form object
			if ($this->_getParam('post_ok')) {
				$this->_actions['ok'] = $this->_getParam('post_ok');
			}
		} else {
			$this->_context['message'] = 'Missing remote object id';
			$this->_status = 'NOK';
			$this->postDispatch();
		}
	}
	
	public function postDispatch()
	{
		if ($this->_uuid && $this->_refreshCache === true) {
			Registry::set($this->_obj, $this->_uuid, true);
		}
		
		// reinject some data 
		foreach ($this->_post as $key => $val) {
			if (substr($key,0,1) == '_') {
				$this->_data[$key] = $val;
			}
		}
		
		// if a redirect is available for the status, add it to the data
		if (isset($this->_redirects[strtolower($this->_status)])) {
			// declare object in tag parsing class to use it for tag substitution on redirect urls
			if ($this->_obj instanceof ObjectModel\BaseObject || $this->_obj instanceof ObjectModel\DataObject) {
				Core\Tag\ObjectTag::$object = $this->_obj;
			} else if ($this->_obj instanceof Action\AbstractAction) {
				Core\Tag\ObjectTag::$object = $this->_obj->getObject();
			}
					
			$this->_context['redirect'] = Core\Tag::parse($this->_redirects[strtolower($this->_status)]);
		}
			
		if (isset($this->_post['_debug'])) $this->_context['debug'] = Backend::getLastQuery();
		
		$this->_setResponse($this->_status, $this->_data, $this->_context);
	}
	
	protected function _setResponse($status = 'OK', $data, $context = null, $format = 'json')
	{
		$response = array('status' => $status, 'data' => $data, 'context' => $context);
 		switch ($format) {
 			
 			case 'json':
 			default:
 				$this->getResponse()->setHeader('Content-type', 'application/json')->setBody(\Zend_Json::encode($response));
 				if (Core::getEnvData('cache_datasets') !== false) {
	 				$this->getResponse()->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
 					$this->getResponse()->clearHeader('Cache-Control');
 					$this->getResponse()->clearHeader('Pragma');
 				}
 				break;
 		}
 		
 		$this->getResponse()->sendResponse();
		exit();
	}
	
	/**
	 * Define the various redirects base on POST data or object parameters
	 * @param mixed $val
	 */
	protected function _defineRedirect($val)
	{
		if (! is_array($val)) $val = (array) $val;
		
		foreach ($val as $key) {
			$var = 'redirect_' . $key;

			if (isset($this->_post[$var])) {
				$this->_redirects[$key] = $this->_post[$var];
			} else if ($this->_obj instanceof Action\AbstractAction && $this->_obj->getParameter($var)) {
				$this->_redirects[$key] = $this->_obj->getParameter($var);
			}
		}
	}
}
