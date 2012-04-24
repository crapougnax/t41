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

class t41_RestController extends Zend_Controller_Action {

	
	
	public function init() {
		
	}
	
	
	public function autocompleteAction()
	{
		$status = 'OK';
		
		$uuid		= $this->_getParam('uuid');
		
		$data = $error = array();
		
		if ($uuid) {
			
			$obj = Core\Registry::get($uuid);
			
			$obj->setParameter('query', $this->_getParam('q'));
			$obj->setParameter('batch', (int) $this->_getParam('batch'));
			
			try {
				
				$result = $obj->execute();

				if ($result === false) {
				
					$error = Backend::getLastQuery();
					$status = 'NOK';
					
				} else {
				
					$data = $result;
				}
				
			} catch (\Exception $e) {
				/* @todo normally no exception is thrown, we should get a on/off flag */
				$error = $e->getMessage(); //Backend::getLastQuery();
				$status = 'ERR';				
			}
		} else {
			
			$status = 'NOK';
			$error = 'Missing remote object id';
		}

		echo $this->_setResponse($status, $data, $error);
		exit;
	}
	
	
	public function crudAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		
//		Zend_Debug::dump($request->getPost());
		
		$method = $this->_getParam('method');
		$class	= $this->_getParam('model');
		$data	= $request->getPost();
		

		try {
			$action = new Action\CrudAction();
			$action->setClass($class)->setCallback($method)->setContext($data);

			
			Zend_Debug::dump($action->execute());
			Zend_Debug::dump(\t41\Backend::getLastQuery()); die;
			//$response->setHttpResponseCode($action->execute() ? 200 : 403);
			
		} catch (\Exception $e) {
			
			echo $e->getMessage() . $e->getTraceAsString();
			//$response->setHttpResponseCode(500);
			//$response->setException($e);
		}
		
		
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
}
