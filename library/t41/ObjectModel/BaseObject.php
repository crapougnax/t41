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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 862 $
 */

use t41\Core,
	t41\Backend,
	t41\ObjectModel;

/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class BaseObject extends ObjectModelAbstract {

	
	/**
	 * Data object handling properties
	 *
	 * @var t41\ObjectModel\DataObject
	 */
	protected $_dataObject;
	
	
	/**
	 * Array of rules to apply on defined trigger
	 * @var array
	 */
	protected $_rules;
	
	/**
	 * Latest call on object's status
	 * @var t41\Core\Status
	 */
	public $status;

	
	/**
	 * 
	 * Object constructor
	 * @param t41\ObjectModel\DataObject|t41\ObjectModel\ObjectUri|string $val
	 * @param array $params
	 */
	public function __construct($val = null, array $params = null)
	{
		$this->_setParameterObjects();
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}

		/* build data object and populate it if possible */
		if ($val instanceof DataObject) {

			if ($val->getClass() != get_class($this)) {
				
				throw new Exception("Provided Data Object is not build on class definition");
			}
			
			$this->_dataObject = $val;
			
			/* get object rules from config */
			$this->setRules();
						
		} else {
			
			$this->_dataObject = DataObject::factory(get_class($this));
			
			/* get object rules from config */
			$this->setRules();
			
			if (! is_null($val)) {
			
				if (! $val instanceof ObjectUri) {
					
					$val = new ObjectUri($val);
					$val->setClass(get_class($this));
				}
		
				$this->_dataObject->setUri($val);
				$this->read();
			}
		}
	}
	
	
	/**
	 * Data Object URI Accessor
	 * 
	 * @return t41_Object_Uri
	 */
	public function getUri()
	{
		/* @todo check wether the object has been saved */
		/* @todo check wether the object is EMBEDDED save it in memory and generate a memory uri ? */
		if ($this->_dataObject->getUri() == null) {
			
			//$this->save();
		}
		
		return $this->_dataObject->getUri();
	}
	
	
	/**
	 * Returns object's data object
	 * 
	 * @todo protect data object from external changes
	 * @return t41\ObjectModel\DataObject
	 */
	public function getDataObject()
	{
		return $this->_dataObject;
	}
	
	
	/**
	 * 
	 * Populate the object data object with given array and optional mapper
	 * @param array $data
	 * @param t41\Backend\Mapper $mapper
	 */
	public function setData(array $data, Backend\Mapper $mapper = null)
	{
		$this->_dataObject->populate($data, $mapper);
	}
	
	
	/**
	 * Return the property instance matching the given key name
	 * @param string $key
	 * @return t41\ObjectModel\Property\AbstractProperty
	 */
	public function getProperty($key)
	{
		return $this->_dataObject->getProperty($key);
	}
	
	
	public function setProperty($key, $value)
	{
		return $this->_dataObject->$key = $value;
	}
	
	
	public function __call($m, $a)
	{
		$method_begin = substr($m, 0, 3);
		$method_end = strtolower(substr($m, 3));
		
		switch ($method_begin) {
			
			case 'set':
				$this->_triggerRules('before/set/' . $method_end);
				$res = $this->_dataObject->$method_end = $a[0];
				
				if ($res === false) {
					
					throw new Exception('OBJECT_UNKNOWN_PROPERTY', $method_end);
				}
				
				$this->_triggerRules('after/set/' . $method_end);
				break;
			
			/* get a property value with optional parameter passed in $a[0] */
			case 'get':
				if (($property = $this->_dataObject->getProperty($method_end)) !== false) {
					
					$this->_triggerRules('before/get/' . $method_end);
					$prop = $property->getValue(isset($a[0]) ? $a[0] : null);
					$this->_triggerRules('after/get/' . $method_end);
					
					return $prop;
					
				} else {
					
					throw new Exception('OBJECT_UNKNOWN_PROPERTY', $method_end);
				}
				break;
			
			default:
				throw new Exception(array("UNKNOWN_METHOD", $m)); 
				break;
		}
		
		return $this;
	}
	
	/**
	 * Set a new status message for the object
	 * 
	 * @param string $message
	 * @param integer $code
	 * @param mixed $context
	 */
	public function declareStatus($message, $code = null, array $context = array())
	{
		$context['class'] = get_class($this);
		$this->status = new Core\Status($message,  $code, $context);
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
			
			//echo 'no rule for ' . $trigger . "\n";
			/* return true if no defined rule for trigger */
			return true;
		}
		
		$result = true;
		
		foreach ($this->_rules[$trigger] as $rule) {

			$result = $result && $rule->execute($this);
		}
		
		return $result;
	}
	
	
	public function setRules(array $rules = array())
	{
		if (count($rules) == 0) {
			
			$rules = (array) ObjectModel::getRules($this);
		}
		
//		\Zend_Debug::dump($rules);
		
		// attach each rule to the relevant property
		foreach ($rules as $trigger => $rulesArray) {
			
			$parts = explode('/', $trigger);
			foreach ($rulesArray as $key => $rule) {
				
				if (($property = $this->_dataObject->getProperty($parts[2])) !== false) {
					
					$property->attach($rule, $parts[0] . '/' . $parts[1]);
				
					// unset delegated rule @todo handle no-property-related rules
					unset($rules[$trigger][$key]);
				}
			}
		}
			
		$this->_rules = $rules;
		
		return $this;
	}
	
	
	/**
	 * Get object data from backend
	 * 
	 *  By default, backend to use is given by data object. It is possible to use another backend by giving a
	 *  instance of a backend adapter implementing t41_Backend_Adapter_Interface
	 *   
	 * @param t41_Backend_Adapter_Interface $backend
	 */
	public function read(Backend\Adapter\AdapterAbstract $backend = null)
	{
		return Backend::read($this->_dataObject, $backend);
	}
	
	
	/**
	 * Save object
	 * 
	 * @return boolean
	 */
	public function save(Backend\Adapter\AdapterAbstract $backend = null)
	{
		return Backend::save($this->_dataObject, $backend);
	}
	
	
	/**
	 * Delete object in backend
	 * Object Uri is resetted. Object can then be saved in another backend
	 * @param Backend\Adapter\AdapterAbstract $backend
	 * @return boolean
	 */
	public function delete(Backend\Adapter\AdapterAbstract $backend = null)
	{
		$res = Backend::delete($this->_dataObject, $backend);
		if ($res === true) {
			
			$this->_dataObject->resetUri();
		}
		
		return $res;
	}

	
	public function find(array $conditions = null, array $sortings = null, $offset = 0, $batch = 10)
	{
		$co = new Collection(clone $this->_dataObject);
		if (is_array($conditions)) {
			
			$co->setConditions($conditions);
		}
		
		if (is_array($sortings)) {
			
			$co->setSortings($sortings);
		}
		
		$co->setBoundaryOffset($offset);
		$co->setBoundaryBatch($batch);
		
		$co->find();
		
		return $co;
	}
	
	
	public function reduce(array $params = array())
	{
		/* keep object in registry */
		$uuid = Core\Registry::set($this);
		
		// build an array with remotely callable methods
		$methods = array();
		foreach (get_class_methods($this) as $method) {
			
			if (substr($method,0,1) == '_') continue;
			//$methods[] = $method;
		}
		
		$array = array('uuid' => $uuid);
		if (Core::$env == Core::ENV_DEV) {
			
			$array['class'] = get_class($this);
			if ($this->getUri()) $array['uri'] = $this->getUri()->__toString();
		}
		return array_merge($this->_dataObject->reduce($params), $array);
//		return array_merge_recursive($this->_dataObject->reduce($params), array('methods' => $methods));
	}
}
