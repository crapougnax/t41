<?php

namespace t41;

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
 * @version    $Revision: 937 $
 */

use t41\ObjectModel,
	t41\ObjectModel\Property,
	t41\View;

/**
 * Class providing objects parameters wrapper ensuring basic logic control.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Parameter implements Core\ClientSideInterface {

	
	const BOOLEAN	= 'boolean';
	
	const INTEGER	= 'integer';
	
	const FLOAT		= 'float';
	
	const STRING	= 'string';
	
	const MULTIPLE	= 'array';
	
	const ANY		= 'any';
	
	const OBJECT	= 'object';
	
	
	/**
	 * Array of various objects parameters definitions
	 * 
	 * @var array
	 */
	static protected $_config = array();
	
	
	/**
	 * Allowed values for parameter
	 *
	 * @var array
	 */
	protected $_values = array();
	
	/**
	 * Parameter value
	 *
	 * @var mixed
	 */
	protected $_value;
	
	/**
	 * Array of allowed parameter types
	 *
	 * @var array
	 */

	/**
	 * Parameter type
	 *
	 * @var integer
	 */
	protected $_type;

	/**
	 * Is parameter value protected ?
	 *
	 * @var boolean
	 */
	protected $_protected = false;
	
	
	/**
	 * Parameter constructor
	 *
	 * @param string $type Parameter type
	 * @param mixed $value Parameter value
	 * @param boolean $protected Parameter protection flag
	 * @param array $values Acceptable values for parameter
	 */
	public function __construct($type = self::ANY, $value = null, $protected = false, array $values = null)
	{
		$this->_setType($type);
		$this->_setProtected($protected);

		if (is_array($values)) {
			
			$this->_setValues($values);
		}
		
		$this->setValue($value);
		
	}
	
	
	protected function _setType($type)
	{
		if (in_array($type, array(self::ANY, self::BOOLEAN, self::INTEGER, self::FLOAT, self::STRING, self::MULTIPLE, self::OBJECT))) {
			
			$this->_type = $type;
			
		} else {
			
			throw new Exception("Le type '$type' n'est pas un type acceptable");
		}		
	}
	
	
	protected function _setProtected($bool)
	{
		$this->_protected = (bool) $bool;
	}
	
	
	protected function _setValues($values)
	{
		$this->_values = $values;
	}
	
	
	protected function _setValue($value)
	{
		if (is_object($value) && count($this->_values) != 0 && !in_array(get_class($value), $this->_values)) {
				
			throw new Exception(array("OBJECT_NOT_INSTANCEOF", array((string) $value, implode(',', $this->_values))));

		} else if (count($this->_values) > 0 && !in_array($value, $this->_values)) {
				
			throw new Exception(array("VALUE_NOT_IN_ENUMERATION", array($value, implode(',', $this->_values))));
		}
		
		$this->_value = $value;
		
		return $this;
	}
	

	/**
	 * Set parameter value
	 * @param mixed $value
	 * @throws \t41\Exception
	 */
	public function setValue($value = null)
	{
		if ($this->_protected && $this->_value != null) {
			
			throw new Exception("VALUE_PROTECTED_FROM_CHANGE");
		}
		
		if (! is_null($value)) {
		
			switch ($this->_type) {
			
				case self::BOOLEAN:
					if (! is_null($value) && ! is_bool($value)) throw new Exception("VALUE_NO_BOOLEAN");
					break;
				
				case self::INTEGER:
					if (! is_integer($value) || ! is_numeric($value)) throw new Exception("VALUE_NO_INTEGER");
					break;
				
				case self::FLOAT:
					if (! is_float($value)) throw new Exception("VALUE_NO_FLOAT");
					break;
				
				case self::OBJECT:
					if (! is_object($value)) throw new Exception(array("VALUE_MUST_BE_INSTANCEOF",$value));
					break;
				
				case self::STRING:
						if (strlen($value) > 0 && ! is_string($value)) throw new Exception(array("VALUE_NO_STRING", $value));
					break;
				
				case self::MULTIPLE:
					if (! is_null($value) && ! is_array($value)) throw new Exception(array("VALUE_NO_ARRAY", $value));
					$value = (array) $value;
					break;
			}
		}
		
		return $this->_setValue($value);
	}
	
	
	/**
	 * Return the current parameter value or the value of a given member 
	 * if key is provided and parameter type is array
	 * @param string $key
	 * @return mixed
	 */
	public function getValue($key = null)
	{
		return ($key && $this->_type == self::MULTIPLE) ? $this->_value[$key] : $this->_value;
	}
	
	
	public function getType()
	{
		return $this->_type;
	}
	
	
	public function isProtected()
	{
		return $this->_protected;
	}
	
	
	/**
	 * Load a configuration file (default value is objects.xml) and add or replace content
	 * 
	 * @param string $file name of file to parse, file should be in application/configs folder
	 * @param boolean $add wether to add to (true) or replace (false) existing configuration data
	 * @return boolean true in case of success, false otherwise
	 */
	static public function loadConfig($file, $add = true)
	{
		$config = Config\Loader::loadConfig($file);

		if ($config === false) {
			return false;
		}
		
		if ($add === false) {
			self::$_config = $config;
		} else {
	        self::$_config = array_merge(self::$_config, $config);
		}
		return true;
	}
	
	
	static protected function _cloneParametersArray($array)
	{
		foreach ($array as $key => $parameter) {
			$array[$key] = clone $parameter;
		}
		return $array;
	}

	
	static public function getParameters($object)
	{
		$class = get_class($object);
		
		// Sometimes, namespaced-class comes without its initial ns separator
		if (substr($class, 0, 1) != '\\') $class = '\\' . $class;

		if ($object instanceof ObjectModel\BaseObject) {
			$params = self::getObjectParameters($class);
			
		} else if ($object instanceof Property\AbstractProperty) {
			$params = self::getPropertyParameters($class);
		
		} else if ($object instanceof View\ViewObject || $object instanceof View\Action\AbstractAction) {
			$params = self::getViewObjectParameters($class);
		
		} else if ($object instanceof View\Decorator\AbstractDecorator) {
			$params =  self::getDecoratorParameters($class);
		}
		
		if (isset($params)) {
			return $params;
		} else if ($object instanceof ObjectModel\ObjectModelAbstract) {
			return self::getCoreParameters($class);
		}
	}
	
	
	static public function getCoreParameters($objectClass)
	{
		if (! isset(self::$_config['core'])) {
			/* @todo get config from t41_Object */
			self::loadConfig('parameters/core.xml');
		}
		$array = self::_compileFragments($objectClass, 'core');

		// transform each array value into t41_Parameter
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;
	}
	
	
	static public function getObjectParameters($objectClass)
	{
		if (! isset(self::$_config['objects'])) {
			/* @todo get config from t41_Object */
			self::loadConfig('objects.xml');
		}
		$array = self::_compileFragments($objectClass);

		// transform each array value into t41_Parameter
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;
	}
	
	
	static public function getPropertyParameters($objectClass)
	{
		if (! isset(self::$_config['properties'])) {
			self::loadConfig('parameters/properties.xml');
		}

		$array = self::_compileFragments($objectClass, 'properties');
		// transform each array value into t41_Parameter
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;
	}
	
	
	static public function getViewObjectParameters($objectClass)
	{
		if (! isset(self::$_config['view_objects'])) {
			self::loadConfig('parameters/view/objects.xml');
		}
		$array = self::_compileFragments($objectClass, 'view');
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;
	}
	
	
	static public function getDecoratorParameters($objectClass)
	{
		$elems = explode('\\', $objectClass);
		
		$sublevel = $elems[count($elems)-1];
		unset($elems[count($elems)-1]);
		
		$class = implode('\\', $elems);
		
		if (! isset(self::$_config['decorators'])) {
			self::loadConfig('parameters/view/decorators.xml');
		}
		$array = self::_compileFragments($class, 'decorators', $sublevel);
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;		
	}
	
	
	/**
	 * Compile fragments of xml configuration 
	 * 
	 * @param string $objectClass
	 * @param string $objectType
	 * @param string $subLevel		sublevel path where data should exist (ex: web/default for a default web decorator)
	 * @return array
	 */
	static protected function _compileFragments($objectClass, $objectType = 'objects', $subLevel = null)
	{
		$array = array();
		
		if (isset(self::$_config[$objectType][$objectClass])) {
			$sub = self::$_config[$objectType][$objectClass];
			
			if ($subLevel) {
				$levels = explode('/', $subLevel);
				foreach ($levels as $level) {
					if (isset($sub[$level])) {
						$sub = $sub[$level];
					} else {
						return $array;
					}
				}

			}
			if (isset($sub['parameters']) && is_array($sub['parameters'])) {
				$array += $sub['parameters'];
			}
			
			/* if class extends another, get parent parameters */
			if (isset($sub['extends']) && ! empty($sub['extends'])) {
				$array += self::_compileFragments($sub['extends'], $objectType, $subLevel);
			}
		}
		
		return $array;
	}
	
	
	static protected function _arrayToParameters(array $array)
	{
		foreach ($array as $key => $value) {
				
			/* ignore parameter without any given type */
			if (! isset($value['type']) || empty($value['type'])) continue;
				
			$array[$key] = new self($value['type']
								  , isset($value['defaultvalue']) ? $value['defaultvalue'] : null
								  , isset($value['protected']) ? (bool) $value['protected'] : false
								  , isset($value['values']) ? $value['values'] : null
								   );
		}
		
		return $array;
	}
	
	
	public function reduce(array $params = array(), $cache = true)
	{
		return $this->_value; //
		return array('id' => $this->_id, 'type' => $this->_type, 'value' => $this->_value);
	}
}
