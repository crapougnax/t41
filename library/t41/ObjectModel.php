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
 * @version    $Revision: 886 $
 */

/**
 * Class providing basic functions needed to handle model objects
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectModel {
	
	
	const ID	= 'id';
	
	const URI	= 'uri';
	
	const MODEL = 'model';
	
	const DATA	= 'data';
	
	
	/**
	 * calculation flags
	 * @var integer
	 */
	const CALC_SUM	= 'sum';
	
	const CALC_AVG	= 'avg';
	
	
	/**
	 * Array of objects definitions
	 * 
	 * @var array
	 */
	static protected $_config = array();
	

	/**
	 * Load a configuration file (default value is objects.xml) and add or replace content
	 * 
	 * @param string $file name of file to parse, file should be in application/configs folder
	 * @param boolean $add wether to add to (true) or replace (false) existing configuration data
	 * @return boolean true in case of success, false otherwise
	 */
	static public function loadConfig($file = 'objects.xml', $add = true)
	{
		$config = Config\Loader::loadConfig($file);
		
		if ($config === false) {
			
			return false;
		}
		
		if ($add === false) {
        
			self::$_config = $config['objects'];
		
		} else {
			
	        self::$_config = array_merge(self::$_config, $config['objects']);
		}

//		\Zend_Debug::dump(self::$_config); die;
		return true;
	}
	
	
	/**
	 * Add or replace an object definition in the configuration
	 * 
	 * @param $id id of object definition
	 * @param $array	array of formatted data defining properties and parameters
	 * @param $force	if set to true and a definition already exists for the given id, the new definition silently replaces the previous one 
	 * 					in any othe case, if definition already exists, method don't add definition and returns false  
	 * @return boolean  true in cas of success, false in any other case
	 */
	static public function addDefinition($id, array $array, $force = false)
	{
		if (self::definitionExists($id) === true && $force !== true) {

			return false;
		}
		
		// @todo check array validity
		self::$_config[$id] = $array;
		
		return true;
	}
	
	
	/**
	 * Returns true if an object definition for the given id exists
	 * 
	 * @param $id
	 * @return boolean
	 */
	static public function definitionExists($id)
	{
		return isset(self::$_config[$id]);
	}
	
	
	/**
	 * Returns an instance of an object based on definition matching the given id
	 * 
	 * @param string|ObjectModel\Uri $param class id or object uri
	 * @throws ObjectModel\Exception
	 * @return ObjectModel\Model
	 */
	static public function factory($param)
	{
		$class = ($param instanceof ObjectModel\ObjectUri) ? $param->getClass() : $param;

		if (! array_key_exists($class, self::$_config)) {
			
			throw new ObjectModel\Exception(array('NO_CLASS_DECLARATION', $class));
		}
		
		try {
			
			$obj = new $class($param instanceof ObjectModel\ObjectUri ? $param : null);
			
		} catch (ObjectModel\Exception $e) {
			
			die($e->getMessage());
			
		} catch (ObjectModel\DataObject\Exception $e) {
			
			die($e->getMessage());
		}
		
		return $obj;
	}
	

	static public function getObjectExtends($key)
	{
		return isset(self::$_config[$key]['extends']) ? self::$_config[$key]['extends'] : false;
	}
	
	
	static public function getObjectProperties($key)
	{
		return isset(self::$_config[$key]) ? self::$_config[$key]['properties'] : array();
	}
	
	
	/**
	 * Returns the matching t41_Property_* object instance
	 * 
	 * @param string $str value must be of form <class_id>.<property_id>
	 * @return t41_Property_Abstract
	 * @throws ObjectModel\Exception
	 */
	static public function getObjectProperty($str)
	{
		list($class, $property) = explode('.', $str);
		
		if (! $class || ! $property) {
			
			throw new ObjectModel\Exception(array("INCORRECT_PROPERTY_DESCRIPTOR", $str));
		}
		
		$props = self::getObjectProperties($class);
		
		if (isset($props[$property])) {
			
			require_once 't41/Property.php';
			return ObjectModel\Property::factory($property, $props[$property]['type'], $props[$property]);
			
		} else {
			
			require_once 't41/Object/Exception.php';
			throw new ObjectModel\Exception("NO_SUCH_PROPERTY");
		}
	}
	
	
	/**
	 * Tests if a definition exists for given $id
	 * Returns a t41_Backend_Adapter_Interface instance if object definition includes a default backend value
	 * 
	 * @param string $id
	 * @return t41_Backend_Adapter_Interface
	 * @throws ObjectModel\Exception
	 */
	static public function getObjectBackend($id)
	{
		if (! self::definitionExists($id)) {
			
			throw new ObjectModel\Exception(array('NO_CLASS_DECLARATION', $id));
		}
		
//		Zend_Debug::dump(self::$_config[$id]);

		if (isset(self::$_config[$id]['backend'])) {
			
			return Backend::getInstance(Backend::PREFIX . self::$_config[$id]['backend']);
		
		} else {

			return Backend::getDefaultBackend();
		}
	}
	
	
	static public function getRules($object)
	{
		$class = get_class($object);
		
		if (! self::definitionExists($class)) {
				
			throw new ObjectModel\Exception(array('NO_CLASS_DECLARATION', $class));
		}
	
		if (! isset(self::$_config[$class]['rules'])) {
				
			return null;
		}
	
		$rules = array();
	
		foreach (self::$_config[$class]['rules'] as $key => $val) {
	
			$rule = ObjectModel\Rule::factory($val['type']);
			$rule->setId($key);
			$rule->setObject($object);
	
			if (isset($val['source'])) 			$rule->setSource($val['source']);
			if (isset($val['destination']))		$rule->setDestination($val['destination']);
				
			$trigger = $val['trigger'];
			$ruleKey = $trigger['when'] . '/' . $trigger['event'];
			if (isset($trigger['property']) && !empty($trigger['property'])) $ruleKey .= '/' . $trigger['property'];
	
			$rules[$ruleKey][$key] = $rule;
		}
	
		ksort($rules);
	
		return $rules;
	}
	
	
	/**
	 * Objects collection factory
	 * @param string $class
	 * @return t41\ObjectModel\Collection
	 * @throws t41\ObjectModel\Exception
	 */
	static public function collectionFactory($class)
	{
		try {
			$do = ObjectModel\DataObject::factory($class);
			$collection = new ObjectModel\Collection($do);
			return $collection;
		} catch (\Exception $e) {
			
			throw new Exception($e->getMessage());
		}
	}
}
