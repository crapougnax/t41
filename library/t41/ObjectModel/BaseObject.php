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

use t41\ObjectModel\Property\AbstractProperty;

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
			
			// @todo cache here
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
	
	
	public function setUri($identifier)
	{
		if ($this->_uri instanceof ObjectUri) {
			throw new Exception("Identifier can only be defined if object uri is null");
		}
		$this->_dataObject->setUri($identifier);
		return $this;
	}
	
	
	public function getIdentifier()
	{
		return $this->_dataObject->getUri() ? $this->_dataObject->getUri()->getIdentifier() : false;
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
	
	
	/**
	 * Add a new dynamic property to the object
	 * @param AbstractProperty $property
	 */
	public function addProperty(AbstractProperty $property)
	{
		$this->_dataObject->addProperty($property);
		return $this;
	}
	
	
	/**
	 * Load and return the blob value of the given property
	 * @param string $propertyName
	 * @return boolean
	 */
	public function loadBlob($propertyName)
	{
		if (($property = $this->getProperty($propertyName)) !== false) {
			return Backend::loadBlob($this->_dataObject, $property);
		} else {
			return false;
		}
	}
	
	
	/**
	 * Magic method to access a property value
	 *
	 * @param string $key
	 * @return t41\ObjectModel\Property\AbstractProperty
	 */
	public function __get($key)
	{
		return $this->_dataObject->$key;
	}
	

	/**
	 * Magic method to set a property value
	 *
	 * @param string $key
	 * @param mixed $val
	 */
	public function __set($key,$val)
	{
		$this->__call('set' . $key, array($val));
	}
	
	
	public function __call($m, $a)
	{
		$method_begin = substr($m, 0, 3);
		$method_end = strtolower(substr($m, 3));
		
		switch ($method_begin) {
			
			case 'has':
				// returns true if property exists
				return (bool) $this->_dataObject->getProperty($method_end);
				break;
				
			// reset a property value to null
			case 'del':
				$this->_triggerRules('before/set/' . $method_end, $this->_dataObject);
				$res = $this->_dataObject->getProperty($method_end);
				if ($res === false) {
					throw new Exception('OBJECT_UNKNOWN_PROPERTY', $method_end);
				}
				$res->resetValue();
				$this->_triggerRules('after/set/' . $method_end, $this->_dataObject);
				break;
				
			case 'set':
				$this->_triggerRules('before/set/' . $method_end, $this->_dataObject);
				$res = $this->_dataObject->$method_end = $a[0];
				
				if ($res === false) {
					throw new Exception('OBJECT_UNKNOWN_PROPERTY', $method_end);
				}
				
				$this->_triggerRules('after/set/' . $method_end, $this->_dataObject);
				break;
			
			/* get a property value with optional parameter passed in $a[0] */
			case 'get':
				if (($property = $this->_dataObject->getProperty($method_end)) !== false) {
					$this->_triggerRules('before/get/' . $method_end, $this->_dataObject);
					$prop = $property->getValue(isset($a[0]) ? $a[0] : null);
					$this->_triggerRules('after/get/' . $method_end, $this->_dataObject);
					return $prop;
					
				} else {
					throw new Exception('OBJECT_UNKNOWN_PROPERTY', $method_end);
				}
				break;

			/* get a property value without triggering rules */
			// @todo untested
			case 'got':
				if (($property = $this->_dataObject->getProperty($method_end)) !== false) {
					$prop = $property->getValue(isset($a[0]) ? $a[0] : null);
					return $prop;
							
				} else {
					throw new Exception(array('OBJECT_UNKNOWN_PROPERTY', $method_end));
				}
				break;	
				
			default:
				throw new Exception(array("UNKNOWN_METHOD", $m)); 
				break;
		}
		
		return $this;
	}
	
	
	/**
	 * Clone object's data object and parameters but keeps values
	 * use reset() to reset properties values to their initial state (first value setted or default value)
	 * @see t41\ObjectModel.ObjectModelAbstract::__clone()
	 */
	public function __clone()
	{
		$this->_dataObject = clone $this->_dataObject;
		
		// change rules' bound object reference
		foreach ($this->_dataObject->getProperties() as $property) {
			$property->changeRulesObjectReference($this);
		}
		
		// clone parameters
		foreach ($this->_params as $key => $val) {
			$this->_params[$key] = clone $val;
		}
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
	
	
	public function setRules(array $rules = array())
	{
		if (count($rules) == 0) {
			$rules = (array) ObjectModel::getRules($this);
		}
		
		// attach each rule to the relevant property
		foreach ($rules as $trigger => $rulesArray) {
			
			$parts = explode('/', $trigger);
			foreach ($rulesArray as $key => $rule) {
				
				// rules on properties
				if (isset($parts[2]) && ($property = $this->_dataObject->getProperty($parts[2])) !== false) {
					$property->attach($rule, $parts[0] . '/' . $parts[1]);
					unset($rules[$trigger][$key]);
					
					/*
					 * @todo find  a way to also pass rules to whatever property is concerned
					 * ex: if the rules allows to compute data from a collection, any change to the collection
					 * members should trigger the rule.
					 */
				} else {
					$this->attach($rule, $parts[0] . '/' . $parts[1]);
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
	public function read(Backend\Adapter\AbstractAdapter $backend = null)
	{
		return Backend::read($this->_dataObject, $backend);
	}
	
	
	/**
	 * Save object (create or update)
	 * 
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 * @return boolean
	 */
	public function save(Backend\Adapter\AbstractAdapter $backend = null)
	{
		$this->_triggerRules('before/save');
		if (! $this->_uri instanceof ObjectUri) {
			$new = true;
			$this->_triggerRules('before/create');
		}
		$result = Backend::save($this->_dataObject, $backend);
		$this->_triggerRules('after/save');
		if (isset($new)) {
			$this->_triggerRules('after/create');
		}
		
		return $result;
	}
	
	
	/**
	 * Delete object in backend
	 * Object Uri is resetted. Object can then be saved in another backend
	 * @param Backend\Adapter\AbstractAdapter $backend
	 * @return boolean
	 */
	public function delete(Backend\Adapter\AbstractAdapter $backend = null)
	{
		$res = Backend::delete($this->_dataObject, $backend);
		if ($res === true) {
			
			$this->_dataObject->resetUri();
			
		} else {
			
			$this->status = new Core\Status('error',null,Backend::getLastQuery());
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
	
	
	/**
	 * (non-PHPdoc)
	 * @see t41\ObjectModel.ObjectModelAbstract::reduce()
	 */
	public function reduce(array $params = array(), $cache = true)
	{
		/* keep object in registry (force refresh) */
		$uuid = $cache ? Core\Registry::set($this, null, true) : null;
		
		// build an array with remotely callable methods
		$methods = array();
//		foreach (get_class_methods($this) as $method) {
			
//			if (substr($method,0,1) == '_') continue;
			//$methods[] = $method;
//		}
		
		$array = $uuid ? array('uuid' => $uuid) : array();
		$array['value'] = $this->__toString();

		return array_merge($this->_dataObject->reduce($params, false), $array);
	}
	
	
	public function reclaimMemory()
	{
		$this->_dataObject->reclaimMemory();
	}
	
	
	public function __toString()
	{
		return sprintf("Redeclare the __toString() method in your '%s' object if you want to display its representation as as string", get_class($this));
	}
	
	
	/**
	 * Proxy method for DataObject::populate()
	 * @param array $data
	 * @return boolean
	 */
	public function populate(array $data)
	{
		return $this->_dataObject->populate($data);
	}
	
}
