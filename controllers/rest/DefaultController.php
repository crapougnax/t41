<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core,
	t41\ObjectModel,
	t41\ObjectModel\Collection,
	t41\Backend,
	t41\View,
	t41\View\Action;

require_once 'Zend/Controller/Action.php';

class Rest_DefaultController extends Zend_Controller_Action {

	
	protected $_obj = null;
	
	
	protected $_status = 'OK';
	
	
	protected $_data;
	
	
	protected $_context = array();

	
	protected $_redirects = array();
	
	
	protected $_post;
	
	
	public function init()
	{
		/* surcharge wth ACL in your own controller */
	}
	
	public function preDispatch()
	{
		$this->_post = $this->getRequest()->getPost();

		$uuid = $this->_getParam('uuid');
	
		if ($uuid) {
	
			$this->_obj = Core\Registry::get($uuid);
			$this->_defineRedirect(array('ok','nok','err'));
	
			if ($this->_obj instanceof ObjectModel\ObjectUri) {
	
				$this->_obj = ObjectModel::factory($this->_obj);
			}
	
			if (! $this->_obj) {
	
				$this->_context['message'] = "Unable to restore object";
				$this->_status = 'ERR';
				$this->postDispatch();
			}

			
		} else {
	
			$this->_context['message'] = 'Missing remote object id';
			$this->_status = 'NOK';
			$this->postDispatch();
		}
	}
	
	
	public function postDispatch()
	{
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
			
		if (Core::$env == Core::ENV_DEV) $this->_context['debug'] = Backend::getLastQuery();
		
		exit ($this->_setResponse($this->_status, $this->_data, $this->_context));
	}
	
	
	protected function _setResponse($status = 'OK', $data, $context = null, $format = 'json')
	{
		$response = array('status' => $status, 'data' => $data, 'context' => $context);
		
 		switch ($format) {
 			
 			case 'json':
 			default:
 				
 				return \Zend_Json::encode($response);
 				break;
 		}
		
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
