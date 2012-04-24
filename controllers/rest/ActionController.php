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


require_once 'DefaultController.php';


class Rest_ActionController extends Rest_DefaultController {

	
	public function __call($methodName, $args)
	{
		if (! $this->_obj instanceof Action\AbstractAction) {
			
			$this->_status = 'NOK';
			$this->_context['message'] = "Server object is not an action";
			return;
		}
		
		try {
					
			$result = $this->_obj->execute($this->_post);
			
			if ($result === false) {
			
				$error = Backend::getLastQuery();
				$this->_status = 'NOK';

			} else {

				$this->_data = $result;
			}
		
		} catch (\Exception $e) {
					
			/* @todo normally no exception is thrown, we should get a on/off flag */
			$this->_context['err'] = $e->getMessage(); //Backend::getLastQuery();
			$this->_status = 'ERR';
		}
	}
}
