<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core;
use t41\Backend;
use t41\View\Action;


require_once 'DefaultController.php';


class Rest_ActionController extends Rest_DefaultController {

	
	/**
	 * Map any given action string to the execute() method of the action's object
	 */
	public function __call($methodName, $args)
	{
		if (! $this->_obj instanceof Action\AbstractAction) {
			$this->_status = 'NOK';
			$this->_context['message'] = "Server-side object is not an action";
			return;
		}
		
		try {
			$array = array_merge($this->_post, array('action' => substr($methodName, 0, strlen($methodName)-6)));
			$result = $this->_obj->execute($array);

			if ($result === false) {
				// if result is false, try to get more information from object status or action status
				$status = $this->_obj->getObject() ? $this->_obj->getObject()->status : $this->_obj->status;
				
				if ($status instanceof Core\Status) {
					$this->_context['message'] = $status->getMessage();
					$this->_context['context'] = $status->getContext();
				}
				
				$this->context['debug'] = Backend::getLastQuery();
				$this->_status = 'NOK';

			} else {
				if (is_array($result)) {
					$this->_data = $result;
				}
			}
		
		} catch (\Exception $e) {
			/* @todo normally no exception is thrown, we should get a on/off flag */
			$this->_context['err'] = $e->getMessage();
			if (Core::$env == Core::ENV_DEV) {
				$this->_context['trace'] = $e->getTraceAsString();
			}
			$this->_status = 'ERR';
		}
	}
}
