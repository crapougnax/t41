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
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\Core;
use t41\Core\Registry;
use t41\Backend;
use t41\ObjectModel;
use t41\ObjectModel\Property;
use t41\ObjectModel\Property\MetaProperty;
use t41\ObjectModel\Property\AbstractProperty;
use t41\Backend\Condition;
use t41\ObjectModel\Property\ArrayProperty;
use t41\ObjectModel\Property\ObjectProperty;
use t41\ObjectModel\Property\MediaProperty;
use t41\ObjectModel\Property\CollectionProperty;
use t41\ObjectModel\Property\IdentifierProperty;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DataObject extends ObjectModelAbstract {
	
	
	static protected $_registry = array();
	
	
	/**
	 * Object URI where data can be found
	 *
	 * @var t41\ObjectModel\ObjectUri
	 */
	protected $_uri;
	
	/**
	 * Name of class related to data object 
	 *
	 * @var string
	 */
	protected $_class;
	
	/**
	 * Array of t41\ObjectModel\Property\AbstractProperty objects
	 *
	 * @var array
	 */
	protected $_data;
	
	
	protected $_dna;
	
	
	/**
	 * Secret key used to communicate with handling object
	 *
	 * @var string
	 */
	protected $_key;
	
	
	protected $_rules;
	
	/**
	 * Class constructor
	 * 
	 * @param string $class
	 * @param array $data
	 */
	public function __construct($class, array $data = null)
	{
		$this->_setClass($class);
		if (! is_null($data)) {
			$this->_setDna($data);
			$this->_setProperties($data);
		}
		
		// set a key to communicate with handling object
		$this->_key = substr(md5(time()), rand(0,27), 5);
	}
	

	/**
	 * Return the identifier or the identifier chain of the current data object
	 * @return mixed
	 */
	public function getIdentifier()
	{
		return $this->getUri() ? $this->getUri()->getIdentifier() : false;
	}
	
	
	/**
	 * Magic method to access a property value
	 * 
	 * @param string $key
	 * @return t41\ObjectModel\Property\AbstractProperty
	 */
	public function __get($key)
	{
	    return isset($this->_data[$key]) ? $this->_data[$key]->getValue() : false;
	}
	
	
	/**
	 * Magic method to set a property value
	 * 
	 * @param string $key
	 * @param mixed $element
	 * @return boolean
	 */
	public function __set($key, $element)
	{
	    return (array_key_exists($key, $this->_data)) ? $this->_data[$key]->setValue($element) : false;
	}
	
	
    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }

    
    /**
     * Set parent object access uri
     * 
     * @param t41\ObjectModel\ObjectUri|string $uri
     * @param boolean $recursion
     * @return t41\ObjectModel\DataObject
     */
    public function setUri($uri, $recursion = false)
    {
    	if (! $uri instanceof ObjectUri) {
    		if ($this->_uri) {
    			throw new Exception("Uri can only be set as a string when object is new");
    		}
    		$this->_uri = $uri;
    		return $this;
    	}

    	$this->_uri = $uri;
    	
    	// populate new uri in properties 'parent' parameter
    	// careful for recursion!
    	if ($recursion === false) {	
	    	foreach ($this->_data as $property) {
    			if ($property->getParent()) {
    				//$property->getParent()->setUri($uri, true);
    				$property->setParent($this);
    			} else {
    				//var_dump($property->getParent()); die;
    				throw new Exception(sprintf('Property %s in %s object is missing a parent reference'
    									, $property->getId(), $this->_class));
    			}
    		}
    	}
    	return $this;
    }

    
    /**
     * Reset data object ObjectUri instance
     * @param boolean $recursion
     */
    public function resetUri($recursion = false)
    {
    	$this->_uri = null;
        
    	// reset uri in properties 'parent' parameter
    	// careful for recursion!
    	if ($recursion === false) {	
	    	foreach ($this->_data as $property) {
    			$property->getParent()->resetUri(true);
    		}
    	}

    	return $this;
    } 
    
	/**
	 * @return t41\ObjectModel\ObjectUri
	 */
    public function getUri()
    {
    	return $this->_uri;
    }
    
    
    /**
     * Reset parent object uri
     * 
     * @return t41\ObjectModel\DataObject
     */
    public function clearUri()
    {
    	$this->_uri = null;
    	return $this;
    }
    
    
    protected function _setClass($class)
    {
    	$this->_class = $class;
    	$this->_setDna($class);
    	$this->_setProperties($class);
    }
    

    protected function _setDna($class)
    {
    	$this->_dna = ObjectModel::getObjectDna($class);
    }
    
    
    protected function _setProperties($var)
    {  	
    	$this->_data = array();
    	
    	if (ObjectModel::getObjectExtends($var)) {
    		$properties = ObjectModel::getObjectProperties(ObjectModel::getObjectExtends($var));
    	} else {
    		$properties = array();
    	}
    	
    	if (is_array(ObjectModel::getObjectProperties($var))) {
	    	$properties += ObjectModel::getObjectProperties($var);
    	}
    		
    	if ($properties !== false) {
    		foreach ($properties as $propertyId => $propertyParams) {
    			$type = isset($propertyParams['type']) ? $propertyParams['type'] : null;
    			if (is_string($propertyParams)) {
    			    continue; // zero-property objects like HybridObject() can cause error
    			}
    			$this->_data[$propertyId] = Property::factory($propertyId, $type, $propertyParams);
    			$this->_data[$propertyId]->setParent($this);
    		}
    	} else {
    		throw new DataObject\Exception(array("MISSING_DEFINITION", $var));
    	}
    }
    
    
    /**
     * Add a dynamic property to an already declared data object
     * @param AbstractProperty $property
     * @return t41\ObjectModel\DataObject
     */
    public function addProperty(AbstractProperty $property)
    {
    	$property->setParent($this);
    	$this->_data[$property->getId()] = $property;
    	$this->_dna['custom'] = true;
    	return $this;
    }
    
    
    /**
     * returns Data Object related class name
     *
     * @return string
     */
    public function getClass()
    {
    	return $this->_class;
    }
    
    
    
	/**
	 * Return a recursive array of all (or changed) properties and their respective value
	 * 
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 * @param boolean $changed
	 * @param boolean $display
	 * @return array
	 * @todo implement backend-related specifications (mapper...)
	 */
    public function toArray(Backend\Adapter\AbstractAdapter $backend = null, $changed = false, $display = false)
    {
    	if (is_null($backend)) {
    		$backend = ObjectModel::getObjectBackend($this->_class);
    	}
    	
    	$result = array('data' => array(), 'collections' => array());
    	
    	/* @var $value t41\ObjectModel\Property\AbstractProperty */
    	foreach ($this->_data as $key => $value) {
    		// meta properties are ignored
    		if ($value instanceof MetaProperty) continue;
    		
    		// should we consider only changed properties (except for collection which status is ambiguous) ?
    		if (! $value instanceof CollectionProperty && $changed === true && $value->hasChanged() !== true) {
    			continue;
    		}
    		
    		if ($display === true && ! $value instanceof Property\CurrencyProperty) {
    			// if $display is set to TRUE, store property's display value
    			$result['data'][$key] = $value->getDisplayValue();
    			continue;
    		}
    		
    		if ($value instanceof Property\CollectionProperty) {
    			
    			if ($value->getParameter('embedded') == true) {
    				$array = array();
    				
    				/* @var $member t41\ObjectModel\BaseObject */
    				foreach ($value->getValue()->getMembers() as $member) {
    					$array[] = $member->getDataObject()->toArray();
    				}
    				$result['data'][$key] = $array;
    			
    			} else {
    			
    				// this property is not part of the saved data set
    				$result['collections'][$key] = $value;
    				continue;
    			}
    			
    		} else if ($value instanceof Property\ObjectProperty || $value instanceof Property\MediaProperty) {
    			$value = $value->getValue();
    			$doBackend = ($this->_uri instanceof ObjectUri) ? $this->_uri->getBackendUri() : ObjectModel::getObjectBackend($this->_class);
				$doBackend = $doBackend->getAlias();
    			
    			if ($value instanceof BaseObject) {
    				if (! $value->getUri()) {
    					// object has not been saved yet
    					$value = $value->getDataObject()->toArray($backend, $changed, $display);
    				} else {
	    				$value = $value->getUri();
		    			/* check backends if they're identical, just keep identifier value*/
    					if ($value->getBackendUri()->getAlias() == $doBackend) {
    						$value = $value->getIdentifier();
    					} else {
    						$value = $value->__toString();
    					}
    				}
    			} else if ($value instanceof self) {
    				
    				if (! $value->getUri()) {
    					$value = $value->toArray();
    				
    				} else {
    					$value = $value->getUri();
    				
    					/* check backends if they're identical, just keep identifier value*/
    					if ($value->getBackendUri()->getAlias() == $doBackend) {
    						$value = $value->getIdentifier();
    						
    					} else {
    						$value = $value->__toString();
    					}
    				}
    				
    			} else if ($value instanceof ObjectUri) {
    				/* check backends if they're identical, just keep identifier value*/
    				if ($value->getBackendUri()->getAlias() == $doBackend) {
    					$value = $value->getIdentifier();
    				} else {
    					$value = $value->__toString();
    				}
    			}
    			
    			$result['data'][$key] = $value;
    			
    		} else if ($value instanceof Property\ArrayProperty) {
    			$result['data'][$key] = serialize($value->getValue());
    		} else if ($value instanceof Property\AbstractProperty){
    			$value = $value->getValue();
    			$result['data'][$key] = ($value instanceof ObjectModel) ? $value->getUri() : $value;
    		} else {
    			$result['data'][$key] = $value;
    		}
    	}
    	
    	return $result;
    }
    

    /**
     * Returns the Property object associated with the given key or key chain
     * @param string $name
     * @return Property\AbstractProperty
     */
    public function getProperty($name)
    {
    	return (isset($this->_data[$name])) ? $this->_data[$name] : false; 
    }
    
    
    /**
     * Returns the property matching the pattern in $name, recursively if needed
     * @param string $name
     * @return t41\ObjectModel\Property\AbstractProperty
     */
    public function getRecursiveProperty($name)
    {
    	if ($name == ObjectUri::IDENTIFIER) {
    		return new IdentifierProperty('id');
    	}
    	
    	if (strpos($name, '.') === false) {
    		return $this->getProperty($name);
    	}
    	
    	$parts = explode('.', $name);

    	$data = $this;
    	
    	foreach ($parts as $part) {
	    	$property = $data->getProperty($part);
	    	
	    	// stop recursion if property is an ArrayProperty because recursion is not possible in do's property
	    	if ($property instanceof ArrayProperty) {
	    		return $property;
	    	}
    			 
    		if ($property instanceof Property\ObjectProperty) {
   				if ($property->getValue() instanceof ObjectModel\DataObject) {
   					$data = $property->getValue();
   					
   				} else if ($property->getValue() instanceof BaseObject) {
   					$data = $property->getValue()->getDataObject();
   					
   				} else if ($property->getValue() instanceof ObjectUri) {
   					$data = DataObject::factory($property->getParameter('instanceof'));
   					$data->setUri($property->getValue());
   					Backend::read($data);
   					
   				} else {
   					$data = DataObject::factory($property->getParameter('instanceof'));
   				}
    		}
    	}
    	return $data->getProperty($part) ? $data->getProperty($part) : $property;
    }
    
    
    /**
     * Return the object property matching the given class name or false
     * @param string $class
     * @return string|boolean
     */
    public function getObjectPropertyId($class)
    {
    	foreach ($this->_data as $key => $val) {
    		if (! $val instanceof Property\ObjectProperty) continue;
    		if ($val->getParameter('instanceof') == $class) return $key;
    	}
    	return false;
    }
    
    
    public function getProperties()
    {
    	$array = array();
    	foreach ($this->_data as $key => $val) {
    		$array[$key] = $val;
    	}
    	return $array;
    }
    
    
    public function getDna($gene = null)
    {
    	if ($this->_dna == false) {
    		return false;
    	} else if (is_null($gene)) {
    		return true;
    	}
    	
    	return isset($this->_dna[$gene]);
    }
    

    /**
     * Populates a data object from a key/value array
     *
     * @param array $data
     * @param \t41\Backend\Mapper $mapper
     * @return \t41\ObjectModel\DataObject
     */
    public function populate(array $data, Backend\Mapper $mapper = null)
    {
    	if ($mapper) {
    		// @todo fix compatibility with metakeys in array
    		$data = $mapper->toDataObject($data, $this->_class);
    	}
    	
    	// then sent to data object properties
    	foreach ($data as $key => $value) {
			// don't use empty() to check $value to avoid zero being ignored
    		if (($property = $this->getProperty($key)) !== false && ! is_null($value)) {
    			if ($value == Property::EMPTY_VALUE) {
    				$property->resetValue();
    				continue;
    			}
    			
    			if ($property instanceof ObjectProperty || $property instanceof MediaProperty) {
    				
    				if ($property->getParameter('instanceof') == null) {
    					throw new DataObject\Exception("Parameter 'instanceof' for '$key' in class should contain a class name");
    				}
    				
    				// Specific case of MediaObject()
    				if ($property->getParameter('instanceof') == 't41\ObjectModel\MediaObject') {
    					// new file case : value is a string prepended by  'tmp:'
    					if ($value && substr($value, 0, strlen(MediaObject::TMP_PREFIX)) == MediaObject::TMP_PREFIX) {
    						$parts = explode('|', substr($value,4)); // 0 => hash, 1 => original file name
    						$file =  '/tmp/' . $parts[0];
    						if (file_exists($file)) {
    							$media = new MediaObject();
    							$media->setUri(md5(rand()*microtime()));
    							$media->setLabel($parts[1]);
    							$finfo = finfo_open(FILEINFO_MIME_TYPE);
    							$mime = finfo_file($finfo,$file);
    							$media->setMedia($file); // blob property
    							$media->setSize(filesize($file));
    							$media->setMime($mime);
    							$media->save();
    							$property->setValue($media); // media property
    							unlink($file);
    						}
    						continue; // don't go further in this case
    					}
    				}
    				
    				if ($value && $value != Property::EMPTY_VALUE) {
    					if (is_object($value) && get_class($value) == $property->getParameter('instanceof')) {
    						$property->setValue($value);
    					} else if (substr($value, 0, 4) == 'obj_') {
    						// get object from cache
    						$property->setValue(Core::cacheGet($value));
    					} else if (substr($value, 0, 1) == Backend::PREFIX) {
    						/* get & call object's backend to get a full configured object uri */
    						$backend = ObjectModel::getObjectBackend($property->getParameter('instanceof'));
    						$value = $backend->buildObjectUri($value, $property->getParameter('instanceof'));
    						$property->setValue($value instanceof ObjectUri ? $value : new ObjectUri($value));
    					} else {
    						//$class = $property->getParameter('instanceof');
    						$backend = ObjectModel::getObjectBackend($property->getParameter('instanceof'));
    						$value = $backend->buildObjectUri($value, $property->getParameter('instanceof'));
    						$property->setValue($value);
    					}
    				} else {
    					$property->resetValue();
    				}

    			} else if ($property instanceof Property\CollectionProperty) {
    				// @todo handle collection populating here 
    			} else {
    				// if value is an array, it is most likely a set of multiple options
    				if (is_array($value)) {
    					$value = implode('|', $value);
    				}
	    			$property->setValue($value);
    			}
    		}
    	}
    	return $this;
    }
    
    
    public function populateFromUri(ObjectUri $uri)
    {
    	
    }
    
    
    /**
     * Map properties against mapper to obtain a backend-compatible array
     * 
     * @param t41\Backend\Mapper $mapper
     * @param t41\Backend\Adapter\AbstractAdapter $backend
     * @return array
     */
    public function map(\t41\Backend\Mapper $mapper, $backend)
    {
    	$array = $this->toArray($backend);
		return array('data' => $mapper->toArray($array['data'], $this->_class), 'collections' => $array['collections']);
    }
    
    
    /**
     * Clone properties without changing their respective values, reset uri
     * @see t41\ObjectModel.ObjectModelAbstract::__clone()
     */
    public function __clone()
    {
    	foreach ($this->_data as $key => $property) {
    		$this->_data[$key] = clone $property;
    		$this->_data[$key]->setParent($this);
    	}
    	$this->resetUri();
    }
    
    
    public function delegateRules(array $rules = array())
    {
    	$this->_rules = $rules;
    	return $this;
    }
    
    
    /**
     * Execute defined rules for given trigger
     *
     * @param string $trigger
     * @return boolean
     */
    public function triggerRules($trigger)
    {
    	if (! isset($this->_rules[$trigger])) {
    		/* return true if no defined rule for trigger */
    		return true;
    	}
    
    	$result = true;
    
    	foreach ($this->_rules[$trigger] as $rule) {
    		$result = $result && $rule->execute($this);
    	}
    
    	return $result;
    }
    

    /**
     * Reset the value of given property name or of all properties
     * @param string $name
     * @return boolean
     */
    public function reset($name = null)
    {
    	if (! is_null($name)) {
    		if (isset($this->_data[$name])) {
    			$this->_data[$name]->reset();
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		foreach ($this->_data as $property) {
    			$property->reset();
    		}
    		return true;
    	}    	 
    }
    
    
    /**
     * Check wether the data object has changed
     * @return boolean
     */
    public function hasChanged()
    {
    	foreach ($this->_data as $property) {
    		 if ($property->hasChanged()) {
    		 	return true;
    		 }
    	}
    	return false;
    }
    
    
    /**
     * Reset changed state of property matching $name or all properties
     * @param string $name
     * @return boolean
     */
    public function resetChangedState($name = null)
    {
    	if (! is_null($name)) {
    		if (isset($this->_data[$name])) {
	    		$this->_data[$name]->resetChangedState();
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		foreach ($this->_data as $property) {
    			$property->resetChangedState();
    		}
    		return true;
    	}
    }
    
    
    /**
     * (non-PHPdoc)
     * @see t41\ObjectModel.ObjectModelAbstract::reduce()
     */
    public function reduce(array $params = array(), $cache = true)
    {
    	//$uuid = ($this->_uri instanceof ObjectUri) ? $this->_uri->reduce($params) : null;
    	$uuid = $cache ? Registry::set($this) : null;
    	
    	$props = array();
    	foreach ($this->_data as $key => $property) {
    		if (isset($params['props']) && ! in_array($key, $params['props'])) {
    			continue;
    		}
    		
    		$constraints = $property->getParameter('constraints');
    		
    		// ignore stricly server-side properties
    		if (isset($constraints['serverside'])) continue;
    		
    		$props[$key] = $property->reduce($params, $cache);
    	}
    	return array_merge(parent::reduce($params), array('uuid' => $uuid, 'props' => $props));
    }
    
    
    static public function factory($class)
    {
    	if (isset(self::$_registry[$class]) && self::$_registry[$class] instanceof self) {
    		return clone self::$_registry[$class];
    	}
    	
    	try {
    		$do = new self($class);
    	} catch (Exception $e) {
    		throw new DataObject\Exception("PROPERTY_ERROR " . $e->getMessage());
    	}
    	
    	//self::$_registry[$class] = $do;
    	
    	return $do;
    }
    
    
    /**
     * Function called to free some object's references memory
     * To be used with caution!
     */
    public function reclaimMemory()
    {
    	foreach ($this->_data as $key => $val) {
    		$val->resetValue();
    	}
    	$this->_uri = null;
    }
}
