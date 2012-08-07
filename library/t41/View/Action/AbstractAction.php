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
	t41\Core,
	t41\View,
	t41\View\ViewObject;

/**
 * Abstract class providing basic methods to remote-triggered actions handling
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractAction extends ViewObject {

	
	protected $_obj;
	
	
	protected $_objClass = 't41\ObjectModel\BaseObject';
	
	
	protected $_context = array();
	
	
	protected $_bound;
	
	
	protected $_cachePrefix;
	
	
	public $status;
	
	
	/**
	 * Optional sub-action identifier
	 * @var string
	 */
	protected $_action;
	

	protected $_callbacks;
	

	public function __construct($obj = null, array $params = null)
	{
		$this->_setParameterObjects();

		if (! is_null($obj)) {
			
			$this->setObject($obj);
			$this->_cachePrefix = method_exists($obj, 'getCachePrefix') ? $obj->getCachePrefix() : 'prefix';
		}
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
	}
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function execute($data)
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
	
	
	public function getCallback($key)
	{
		return $this->_callbacks[$key];
	}
	
	
	public function setCallback($key, $str)
	{
		$this->_callbacks[$key] = $str;
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
	
	
	public function register($placeHolder = View::PH_DEFAULT, array $params = null, $clone = false)
	{
		$res = parent::register($placeHolder, $params, $clone);
		if ($res !== false) {
			
			$this->_context['rid'] = $res;
		}
		
		return $res;
	}
	
	
	public function bind(ViewObject $obj)
	{
		$this->_bound = $obj;
		return $this;
	}
	
	
	public function unbind()
	{
		$this->_bound = null;
		return $this;		
	}
	
	
	public function getBoundObject()
	{
		return $this->_bound;
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\ObjectModel.ObjectModelAbstract::reduce()
	 */
	public function reduce(array $params = array(), $cache = true)
	{
		/* keep object in registry */
		$this->setContextData('uuid', Core\Registry::set($this));

		$fullAction = $this->_id;
		if ($this->_action) $fullAction .= '/' . $this->_action;
		
		$array = array(
						'event'		=> $this->getParameter('event'),
						'action'	=> $fullAction,
						'data'		=> $this->getContext(),
						'callbacks'	=> $this->_callbacks
					  );
		
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
