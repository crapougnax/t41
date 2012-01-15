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

use t41\ObjectModel;
use t41\ObjectModel\Property;
use t41\View;

/**
 * Class providing objects parameters wrapper ensuring basic logic control.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Parameter {

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
	}
	
	
	public function setValue($value = null)
	{
//		return $this->_setValue($value);
		if ($this->_protected && $this->_value != null) {
			
			throw new Exception("VALUE_PROTECTED_FROM_CHANGE");
		}
		
		if (is_null($value) || $this->_type == self::ANY) {
			
			return $this->_setValue($value);
			
		} else {
			
			if ($this->_type == self::BOOLEAN && !is_bool($value)) {
			
				throw new Exception("La valeur doit être de type booléen");
			}

			else if ($this->_type == self::INTEGER && (! is_integer($value) || ! is_numeric($value))) {
			
//				var_dump($value); die;
				throw new Exception("La valeur doit être de type entier");
			}
		
			else if ($this->_type == self::FLOAT && !is_float($value)) {
			
				throw new Exception("La valeur doit être de type float");
			}
		
			else if ($this->_type == self::STRING && strlen($value) > 0 && !is_string($value)) {
			
				throw new Exception("La valeur doit être de type string, valeur donnée est '$value'");
			}
			
			else if ($this->_type == self::OBJECT && !is_object($value)) {

				throw new Exception("La valeur doit être de type objet, valeur donnée est '$value'");
			}
		}
		
		return $this->_setValue($value);
	}
	
	
	public function getValue()
	{
		return $this->_value;
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
		require_once 't41/Config.php';
		$config = Config\Loader::loadConfig($file);

		if ($config === false) {
			
			return false;
		}
		
		if ($add === false) {
        
			self::$_config = $config;
		
		} else {
			
	        self::$_config = array_merge(self::$_config, $config);
		}

		//Zend_Debug::dump(self::$_config);
		return true;
	}
	

	static public function getParameters($object)
	{
		$class = get_class($object);
		
		if ($object instanceof ObjectModel\ObjectModel) {
			
			return self::getObjectParameters($class);
		}
		
		if ($object instanceof Property\PropertyInterface) {
			
			return self::getPropertyParameters($class);
		}
		
		if ($object instanceof View\ObjectModel) {
			
			return self::getViewObjectParameters($class);
		}
		
		if ($object instanceof View\DecoratorAbstract) {

			return self::getDecoratorParameters($class);
		}
		
		// lowest level of inheritance
		if ($object instanceof ObjectModel\ObjectModelAbstract) {

			return self::getCoreParameters($class);
		}
	}
	
	
	static public function getCoreParameters($objectClass)
	{
		if (! isset(self::$_config['core'])) {
			
			//die(t41_Core::getBasePath() . 't41/configs/parameters/core.xml');
			/* @todo get config from t41_Object */
			self::loadConfig('parameters/core.xml');
		}

		//Zend_Debug::dump(self::$_config);
		
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
		
		$array = self::_compileFragments($objectClass, 'view_objects');
		
		//Zend_Debug::dump($array); die;
		return (count($array) != 0) ? self::_arrayToParameters($array) : $array;
	}
	
	
	static public function getDecoratorParameters($objectClass)
	{
		$elems = explode('_', $objectClass);
		
		$decoratorId = $elems[count($elems)-1];
		$view = $elems[count($elems)-2];
		unset($elems[count($elems)-1]);
		unset($elems[count($elems)-1]);
		
		$class = implode('_', $elems);
		$sublevel = strtolower($view . '/' . $decoratorId);
		
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
}
