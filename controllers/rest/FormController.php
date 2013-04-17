<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use	t41\Backend;
use t41\View\FormComponent;


require_once 'DefaultController.php';


class Rest_FormController extends Rest_DefaultController {

	
	/**
	 * Map any given action string to the execute() method of the action's object
	 */
	public function saveAction()
	{
		if (! $this->_obj instanceof FormComponent) {
			$this->_status = 'NOK';
			$this->_context['message'] = "Server-side object is not an action";
			return;
		}
		
		try {
			// save form
			$result = $this->_obj->save($this->_post);

			if ($result === false) {
				$this->context['debug'] = Backend::getLastQuery();
				$this->_status = 'NOK';

			} else {
				$this->_data = $this->_obj->getSource()->reduce();
			}
		
		} catch (\Exception $e) {
					
			/* @todo normally no exception is thrown, we should get a on/off flag */
			$this->_context['err'] = $e->getMessage();
			$this->_status = 'ERR';
		}
	}
}
