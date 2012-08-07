<?php

namespace t41\Backend;

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
 * @version    $Revision: 907 $
 */

use t41\Backend;

/**
 * Class providing functionalities to define and use mappers between objects and backends
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Mapper
{
	/**
	 * Character used as a separator between values in a same string
	 *
	 * @var string
	 */
	const VALUES_SEPARATOR = ',';
	
	
	/**
	 * Character used as a separator between arguments in a value
	 *
	 * @var string
	 */
	const ARGS_SEPARATOR = ':';
	
	
	/**
	 * Matching array
	 * 
	 * @var array
	 */
	protected $_mapping;

	
	/**
	 * Class constructor
	 *
	 * @param array $mappingArray Mapping array
	 */
	public function __construct(array $mappingArray)
	{
		$this->_mapping = $mappingArray;
		
		//Zend_Debug::dump($mappingArray);
	}
	
	
	/**
	 * Returns the datastore name of the given property name
	 *
	 * @param string $className
	 * @param string $propertyName
	 * @return string
	 */
	public function propertyToDatastoreName($className, $propertyName = null)
	{
		if (! isset($this->_mapping[$className])) {
			
			return $propertyName;
		}
		
		if (! isset($this->_mapping[$className]['map'][$propertyName])) {
		
			return $propertyName;
		}
		
		return $this->_mapping[$className]['map'][$propertyName]['datastorename'];
	}
	


	/**
	 * Convert the given array to match given object name properties.
	 *
	 * @param array $array Data array
	 * @param string $objectName object class name
	 * @return array
	 */
	public function toDataObject(array $array, $objectName)
	{
		if (! is_array($this->_mapping[$objectName]) || ! is_array($this->_mapping[$objectName]['map']) || count($this->_mapping[$objectName]['map']) == 0) {
			
			return $array;
		}
		
		$array = array_change_key_case($array);
		
		$result = array();

		foreach ($this->_mapping[$objectName]['map'] as $property => $value) {

			if (isset($array[ strtolower($value['datastorename']) ])) {
				
				/* datastore name may contain multiple values when foreign objects/rows are 
				 * referenced with more than one key (DB2 has such cases) */
				if (strpos($value['datastorename'], self::ARGS_SEPARATOR) !== false) {
					
					$val = array();
					$pkeys = explode(self::ARGS_SEPARATOR, $value['datatorename']);
					foreach ($pkeys as $pkey) {
						
						$val[$pkey] = $array[$pkey];
					}
					
				} else {
					
					$val = $array[ strtolower($value['datastorename']) ];
				}

				/* check whether data need to be converted when coming FROM backend */ 
				if (isset($value['convert']) && isset($value['convert']['from'])) {
					
					$func = $value['convert']['from'];
						
					if (strpos($func,'::') !== false) {
							
						$val = call_user_func(explode('::', $func), $val);
						
					} else {
							
						$val = $func($val);
					}
				}
				
				$result[$property] = $val;
				unset($array[$value['datastorename']]);
			}
		}
		
		if (isset($this->_mapping[$objectName]['extends'])) {

			$result += $this->toDataObject($array, $this->_mapping[$objectName]['extends']);
		} 
		
		// add remaining data from original array
		$result['_unmapped'] = $array;
		
		return $result;
	}
	

	public function toArray(array $array, $objectName)
	{
		if (! isset($this->_mapping[$objectName]) || ! isset($this->_mapping[$objectName]['map']) || ! is_array($this->_mapping[$objectName]['map'])) {
			
			return $array;
		}
		
		$result = array();
		$addedArray = $array;
		
		foreach ($this->_mapping[$objectName]['map'] as $property => $value) {

			if (isset($value['datastorename'])) {
				
				$key = $value['datastorename'];
				
				// if datastorename value is set to '_IGNORE_', property is ignored
				if ($key == '_IGNORE_') {
					
					unset($addedArray[$key]);
					continue;
				}
				
				/**
				 * Conversion can lead to the need to create composite values, XML property 'pattern' is designed to that effect
				 * ex: create a LDAP required CN property from the pattern "$lastname, ,$firstname" 
				 */
				if (isset($value['pattern'])) {
					
					$val = null;
					$patternElements = explode(',', $value['pattern']);
					foreach ($patternElements as $patternElement) {
						
						if (substr($patternElement, 0, 1) == '$') {
							
							$patternProperty = substr($patternElement, 1);
							if (isset($array[$patternProperty])) {
								
								$val .= $array[$patternProperty];
							}
							
						} else {
							
							$val .= $patternElement;
						}
					}
					
				} else {
				
					$val = isset($array[$property]) ? $array[$property] : null;
				}

				/* check whether data need to be converted before going TO backend */ 
				if (isset($value['convert']) && isset($value['convert']['to'])) {
					
					$func = $value['convert']['to'];
						
					if (strpos($func,'::') !== false) {
							
						$val = call_user_func(explode('::', $func), $val);
						
					} else {
							
						$val = $func($val);
					}
				}
				
				$result[isset($key) ? $key : $property] = $val;
				
				if (isset($key)) {
					
					unset($addedArray[$property]);
					unset($key);
				}
			}
		}
		
		// add remaining data from original array
		$result += $addedArray;
		
		return $result;
	}
	
	
	public function getDatastore($objectName)
	{
		if (isset($this->_mapping[$objectName])) {
			
			return isset($this->_mapping[$objectName]['datastore']) ? $this->_mapping[$objectName]['datastore'] : null;
		
		} else {
			
			// change to support LDAP usage of datastore 
			// @todo some older code may need changes
			return null; //$objectName;
		}
	}
	

	/**
	 * Returns the value of the dataclass attribute or $objectName
	 * 
	 * @param string $objectName
	 * @return string
	 */
	public function getDataclass($objectName)
	{
		if (isset($this->_mapping[$objectName])) {
			
			return isset($this->_mapping[$objectName]['dataclass']) ? $this->_mapping[$objectName]['dataclass'] : $objectName;
		
		} else {
			
			return $objectName;
		}
	}
	
	
	/**
	 * Returns the defined primary key(s) for the given object class
	 * The simpliest form is a string. If there is more than one primary key or
	 * its value needs to be cast, an array is returned where key is the name of the key 
	 * and value is the type of value that the key accepts.
	 * 
	 * @param string $objectName
	 * @return string|array
	 */
	public function getPrimaryKey($objectName)
	{
		if (! isset($this->_mapping[$objectName]) || ! isset($this->_mapping[$objectName]['pkey'])) {
			
			return Backend::DEFAULT_PKEY;
		}
		
		if (isset($this->_mapping[$objectName]['pkey_parsed'])) {
			
			return $this->_mapping[$objectName]['pkey_parsed'];
		}
		
		$keys  = explode(\t41\Mapper::VALUES_SEPARATOR, $this->_mapping[$objectName]['pkey']);
		
		$array = array();
		
		foreach ($keys as $key) {
			
			$elements = explode(\t41\Mapper::ARGS_SEPARATOR, $key);
			$array[] = new Key($elements[0], isset($elements[1]) ? $elements[1] : null);
		}
		
		// save parsed value
		$this->_mapping[$objectName]['pkey_parsed'] = $array;
		return $array;
	}

	
	public function getExtraArg($key, $class = null)
	{
		if ($class && isset($this->_mapping[$class]['parameters'][$key])) {
			
			return $this->_mapping[$class]['parameters'][$key];
			
		} else if (isset($this->_mapping['parameters'][$key])) {
			
			return $this->_mapping['parameters'][$key];
			
		} else {
			
			return false;
		}
	}
	
	
	public function translate($objectName, $arraySource, $source = 'object')
	{		
		if (! isset ($this->_mapping[$objectName]) || ! isset ($this->_mapping[$objectName]['map'])) {
			
			return $arraySource;
		}
		
		$map = $this->_mapping[$objectName]['map'];
		$arrayDest = array();
		
		foreach ($arraySource as $key => $val) {
			
			if (isset($map[$key])) {
				
				if (isset($map[$key]['datastorename'])) {
					
					$arrayDest[ $map[$key]['datastorename'] ] = $val;
				}
			} else {
				
				$arrayDest[$key] = $val;
			}
 		}
		
		return $arrayDest;
	}
	
	
	public function setDatastoreName($objectName, $datastoreName)
	{
		if (isset($this->_mapping[$objectName])) {
		
			$this->_mapping[$objectName]['datastore'] = $datastoreName;
		}
		return $this;
	}
}
