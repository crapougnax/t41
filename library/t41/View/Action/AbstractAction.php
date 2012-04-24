<?php

namespace t41\View\Action;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\ObjectModel\ObjectModelAbstract,
	t41\Parameter,
	t41\Core;

/**
 * Abstract class providing basic methods to remote-triggered actions handling
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractAction extends ObjectModelAbstract {

	
	protected $_obj;
	
	
	protected $_objClass = 't41\ObjectModel\BaseObject';
	
	
	protected $_context = array();
	
	
	/**
	 * Optional sub-action identifier
	 * @var string
	 */
	protected $_action;
	

	protected $_callback;
	

	public function __construct($obj = null, array $params = null)
	{
		$this->_setParameterObjects();

		if (! is_null($obj)) {
			
			$this->setObject($obj);
		}
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
	}
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return mixed
	 */
	public function execute()
	{
		return true;
	}
	

	/**
	 * Set an object instance to be called within the execute method
	 *
	 * @param t41_Object_Abstract $obj object instance
	 * @return t41_View_Action_Abstract current instance
	 */
	public function setObject($obj)
	{
		if (! $obj instanceof $this->_objClass){

			throw new \Exception("Object should be of type " . $this->_objClass);
		}
		
		$this->_obj = $obj;
		return $this;
	}
	
	
	public function getObject()
	{
		return $this->_obj;
	}
	
	
	public function getCallback()
	{
		return $this->_callback;
	}
	
	
	public function setCallback($str)
	{
		$this->_callback = $str;
		return $this;
	}
	
	
	public function setAction($action)
	{
		$this->_action = $action;
		return $this;
	}
	
	
	public function getAction()
	{
		return $this->_action;
	}
	
	
	public function setContextData($key, $val)
	{
		$this->_context[$key] = $val;
		return $this;
	}
	
	
	public function setContext(array $array)
	{
		foreach ($array as $key => $val) {
			
			$this->setContextData($key, $val);
		}
	}
	
	
	public function getContextData($key)
	{
		return $this->_context[$key];
	}

	
	public function getContext()
	{
		return $this->_context;
	}
	
	
	public function register()
	{
		$res = parent::register();
		if ($res !== false) {
			
			$this->_context['rid'] = $res;
		}
		
		return $res;
	}
	
	
	public function reduce(array $params = array())
	{
		/* keep object in registry */
		$this->setContextData('uuid', Core\Registry::set($this));

		$fullAction = $this->_id;
		if ($this->_action) $fullAction .= '/' . $this->_action;
		
		$array = array(
						'event'		=> $this->getParameter('event'),
						'action'	=> $fullAction,
						'data'		=> $this->getContext(),
					  );
		
		if ($this->_callback) $array['callback'] = $this->_callback;
		
		// add or replace data with optional $params['data'] content 
		if (isset($params['extra'])) {
			
			foreach ((array) $params['extra'] as $key => $val) {
				
				$array[$key] = $val;
			}
		}
		
		// return reduced action without parameters
		return $array;
	}
}
