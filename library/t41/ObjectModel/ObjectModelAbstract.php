<?php

namespace t41\ObjectModel;

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
 * @package    t41_ObjectModel
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */

use t41\Core,
	t41\Parameter;

/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_ObjectModel
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class ObjectModelAbstract implements Core\ClientSideInterface {

	
	/**
	 * Object unique identifier
	 *
	 * @var string
	 */
	protected $_id;
	
	
	/**
	 * Object parameters
	 * a collection of t41\Parameter instances
	 *
	 * @var array
	 */
	protected $_params = array();
	
	
	/**
	 * Array of rules to apply on defined trigger
	 * @var array
	 */
	protected $_rules;
	
	
	/**
	 * Class constructor, defines object id, parameters and their values
	 * 
	 * @param string $id
	 * @param array $params
	 */
	public function __construct($id = null, array $params = null)
	{
		if (! is_null($id)) $this->setId($id);

		$this->_setParameterObjects();
		
		if (is_array($params)) {
			$this->_setParameters($params);
		}
	}
	
	
	/**
	 * Sets object id
	 * 
	 * @param string $id
	 * @return t41_Object_Abstract
	 */
	public function setId($id)
	{
		if (! is_null($this->_id)) {
			
			throw new Exception(array("OBJECT_CANNOT_CHANGE_VALUE", '$id'));
		}
		
		$this->_id = $id;
		return $this;
	}
	
	
	public function getId() 
	{ 
		return $this->_id;
	}		
	
	
	/**
	 * PARAMETERS HANDLING METHODS SECTION
	 */
	
	
	/**
	 * Return the current value of the parameter object matching the given key or key pattern
	 *
	 * @param string $key		parameter key. The simplest form is a string. 
	 * 							The dot is used to directly get an array-type property value (ex: propname.arraykey)
	 * @param boolean $strict	Strict mode. Set to true to thrown an exception if parameter does not exist
	 * @return mixed parameter value
	 */
	final public function getParameter($key, $strict = false)
	{
		$arraykey = null;
		if (strstr($key, '.') !== false) list($key, $arraykey) = explode('.', $key);
		
		if (isset($this->_params[$key]) && $this->_params[$key] instanceof Parameter) {
			
			return $this->_params[$key]->getValue($arraykey);
		
		} else {
			
			if ($strict === false) {
				
				return null;
			
			} else {
			
				throw new Exception(array("NO_SUCH_PARAMETER", $key));
			}
		}
	}
	
	/**
	 * Permet de définir la valeur d'un paramètre ou modifier la valeur d'un paramètre non protégé
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setParameter($key, $value)
	{
		if (isset($this->_params[$key]) && $this->_params[$key] instanceof \t41\Parameter) {
			
			try {
				$this->_params[$key]->setValue($value);
				
			} catch (Exception $e) {
				
				throw new Exception("Impossible de definir le parametre '$key' avec la valeur '$value' : " . $e->getMessage());
			}
		}
		
		return $this;
	}
	
	
	public function getParameters()
	{
		return $this->_params;
	}
	
	
	/**
	 * Allow definition of multiple parameters from an array
	 *
	 * @param array $params
	 */
	final protected function _setParameters(array $params)
	{
		foreach ($params as $key => $value) {
			
			$this->setParameter($key, $value);
		}
	}
	
	
	/**
	 * Allow to add multiple parameters to the class 
	 *
	 * @param array $objects	if parameter is null, parameters are acquired from xml configuration files
	 * @param boolean $replace
	 */
	final protected function _setParameterObjects(array $objects = null, $replace = false)
	{
		if (count($objects) == 0) {
			
			$objects = (array) Parameter::getParameters($this);
		}
		
		if ($replace === true) {
			$this->_params = array();
		}
		
		foreach ($objects as $key => $object) {
			
			if (! $object instanceof Parameter ) {
				continue;
			}
			
			$this->_params[$key] = $object;
		}
	}
	
	
	/**
	 * RULES
	 */
	

	/**
	 * Attach a rule instance and its trigger to the property
	 * @param ObjectModel\Rule\AbstractRule $rule
	 * @param unknown_type $trigger
	 */
	public function attach(AbstractRule $rule, $trigger)
	{
		if (! isset($this->_rules[$trigger]) || ! is_array($this->_rules[$trigger])) {
			$this->_rules[$trigger] = array();
		}
		$this->_rules[$trigger][] = $rule;
		return $this;
	}
	
	
	public function getRules()
	{
		return $this->_rules;
	}


	/**
	 * Execute defined rules for given trigger
	 *
	 * @param string $trigger
	 * @return boolean
	 */
	protected function _triggerRules($trigger)
	{
		if (! isset($this->_rules[$trigger])) {
			/* return true if no defined rule for trigger */
			return true;
		}
	
		$result = true;
	
		foreach ($this->_rules[$trigger] as $rule) {
			//\Zend_Debug::dump($rule); die;
			$result = $result && $rule->execute($this->_dataObject);
		}
	
		return $result;
	}
	
	
	public function __clone()
	{
		foreach ($this->_params as $key => $param) {
			$this->_params[$key] = clone $param;
		}
	}
	
	
	public function register()
	{
		$res = Core::cacheSet($this);
		if ($res !== false) {
			$this->_id = $res;
		}
		
		return $this->_id;
	}
	
	
	/**
	 * Reduce parameters to a simple array
	 * @see t41\Core.ClientSideInterface::reduce()
	 * @return array
	 */
	public function reduce(array $params = array(), $cache = true)
	{
		if (isset($params['params']) && count($params['params']) == 0) return array();
		
		$parameters = array();
		foreach ($this->_params as $key => $parameter) {
			
			if (isset($params['params']) && is_array($params['params']) && ! array_key_exists($key, $params['params'])) {
				continue;
			}
			
			if (is_null($parameter->getValue())) continue;
			$parameters[$key] = $parameter->reduce($params, $cache);
		}
		
		return count($parameters) > 0 ? array('params' => $parameters) : array();
	}
}
