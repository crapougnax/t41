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
 * @version    $Revision: 876 $
 */

use t41\Core,
	t41\Backend,
	t41\ObjectModel,
	t41\ObjectModel\Property;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DataObject extends ObjectModelAbstract {
	
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
			
			$this->_setProperties($data);
		}
		
		// set a key to communicate with handling object
		$this->_key = substr(md5(time()), rand(0,27), 5);
	}
	
	
	/**
	 * Magic method to access a property value
	 * 
	 * @param string $key
	 * @return t41_Property_Abstract
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

    
    public function __unset($key)
    {
        unset($this->_data[$key]);
    }

    
    /**
     * Set parent object access uri
     * 
     * @param t41_Object_Uri $uri
     * @return t41_Data_Object 
     */
    public function setUri(ObjectModel\ObjectUri $uri)
    {
    	$this->_uri = $uri;
    	return $this;
    }

    
    public function resetUri()
    {
    	$this->_uri = null;
    	return $this;
    } 
    
	/**
	 * @return t41_Object_Uri
	 */
    public function getUri()
    {
    	return $this->_uri;
    }
    
    
    /**
     * Reset parent object uri
     * 
     * @return t41_Data_Object
     */
    public function clearUri()
    {
    	$this->_uri = null;
    	return $this;
    }
    
    
    protected function _setClass($class)
    {
    	$this->_class = $class;
    	$this->_setProperties($class);
    }
    
    
    protected function _setProperties($var)
    {  	
    	$this->_data = array();
    	
    	if (ObjectModel::getObjectExtends($var)) {

    		$properties = ObjectModel::getObjectProperties(ObjectModel::getObjectExtends($var));
    		
    	} else {
    		
    		$properties = array();
    	}
    	
    	$properties += (array) ObjectModel::getObjectProperties($var);
    	
//    	\Zend_Debug::dump($properties); die;
    	if ($properties !== false) {
    		
    		foreach ($properties as $propertyId => $propertyParams) {
    			
    			$type = isset($propertyParams['type']) ? $propertyParams['type'] : null;
    			
    			$this->_data[$propertyId] = Property::factory($propertyId, $type, $propertyParams);
    			$this->_data[$propertyId]->setParent($this);
    		}
    		
    	} else {
    		
    		throw new DataObject\Exception(array("MISSING_DEFINITION", $var));
    	}
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
	 * Return an array with all properties
	 * 
	 * @param t41\Backend\Adapter\AbstractAdapter $backend
	 * @param boolean $changed
	 * @return array
	 */
    public function toArray(Backend\Adapter\AbstractAdapter $backend = null, $changed = false)
    {
    	if (is_null($backend)) {
    		
    		$backend = ObjectModel::getObjectBackend($this->_class);
    	}
    	
    	$result = array('data' => array(), 'collections' => array());
    	
    	/* @var $value t41\ObjectModel\Property\AbstractProperty */
    	foreach ($this->_data as $key => $value) {
    		
    		// consider only changed properties?
    		if ($changed === true && $value->hasChanged() === false) continue;
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
    			
    		} else if ($value instanceof Property\ObjectProperty) {
    			
    			$value = $value->getValue();
    			$doBackend = ($this->_uri instanceof ObjectUri) ? $this->_uri->getBackendUri() : ObjectModel::getObjectBackend($this->_class);
				$doBackend = $doBackend->getAlias();
    			
    			if ($value instanceof BaseObject) {
    				
    				// object has not been saved yet
    				if (! $value->getUri()) {

    					$value = $value->getDataObject()->toArray();

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
    
    
    public function getRecursiveProperty($name)
    {
    	if (strpos($name, '.') === false) {
    		
    		return $this->getProperty($name);
    	}
    	
    	$parts = explode('.', $name);

    	$data = $this->_data;
    	foreach ($parts as $part) {

	    	$property = $data[$part];
    			 
    		if ($property instanceof Property\ObjectProperty) {
    	
   				$data = DataObject::factory($property->getParameter('instanceof'));
   				$data = $data->getProperties();
    		}
    	}
    	 
    	return (isset($data[$part])) ? $data[$part] : false;    	
    }
    
    
    public function getProperties()
    {
    	$array = array();
    	
    	foreach ($this->_data as $key => $val) {
    		
    		$array[$key] = clone $val;
    	}
    	
    	return $array;
    }
    

    /**
     * Populates a data object
     *
     * @param array $data
     * @param \t41\Backend\Mapper $mapper
     * @return \t41\ObjectModel\DataObject
     */
    public function populate(array $data, Backend\Mapper $mapper = null)
    {
    	if ($mapper) {
    		
    		$data = $mapper->toDataObject($data, $this->_class);
    	}
    	
    	// then sent to data object properties
    	foreach ($data as $key => $value) {
    		
    		if (isset($this->_data[$key]) && ! empty($value)) {

    			/* @var $property t41_Property_Abstract */
    			$property = $this->_data[$key];
    			
    			if ($property instanceof Property\ObjectProperty) {

    				if ($property->getParameter('instanceof') == null) {
    					
    					//\Zend_Debug::dump($property); die;
    					throw new DataObject\Exception("Parameter 'instanceof' for '$key' in class should contain a class name");
    				}
    				
    				if (substr($value, 0, 1) == Backend::PREFIX) {
    					
		    			$property->setValue(new ObjectUri($value));
    					continue;
    				}
    				
    				$backend = ObjectModel::getObjectBackend($property->getParameter('instanceof'));
    			/*	
    				if ($backend != t41_Backend::getDefaultBackend()) {

    					$uri  = t41_Backend::PREFIX . t41_Object::getObjectBackend($property->getParameter('instanceof'))->getAlias();
    					$ds = $backend->getMapper() ? $backend->getMapper()->getDataStore($property->getParameter('instanceof')) . '/' : null;
	    				$value = $uri . '/' . $ds;
    				}
    			*/	
    				/* call object's backend to get a full configured object uri */
    				$value = $backend->buildObjectUri($value, $property->getParameter('instanceof'));
    			}
    			
    			$property->setValue($value);
    		}
    	}
    	
    	$this->resetChangedState();
    	return $this;
    }
    
    
    /**
     * Map properties against mapper to obtain a backend-compatible array
     * 
     * @param t41\Backend\Mapper $mapper
     * @param t41\Backend\Adapter\AbstractAdapter $backend
     * @return array
     */
    public function map(t41_Backend_Mapper $mapper, $backend)
    {
    	$array = $this->toArray($backend);
    	
		return array('data' => $mapper->toArray($array['data'], $this->_class), 'collections' => $array['collections']);
    }
    
    
    public function __clone()
    {
    	foreach ($this->_data as $key => $property) {
    		if(is_object($property))
    			$this->_data[$key] = clone $property;
    	}
    	
    	if(is_object($this->_uri))
    		$this->_uri = clone $this->_uri;
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
    
    
    public function reduce(array $params = array())
    {
    	$uuid = ($this->_uri instanceof ObjectUri) ? $this->_uri->reduce($params) : null;
    	$props = array();
    	foreach ($this->_data as $key => $property) {
    		
    		if (isset($params['props']) && ! in_array($key, $params['props'])) {
    			
    			continue;
    		}
    		
    		$constraints = $property->getParameter('constraints');
    		
    		// ignore stricly server-side properties
    		if (isset($constraints['serverside'])) continue;
    		
    		$props[$key] = $property->reduce($params);
    	}
    	
    	return array_merge(parent::reduce($params), array('uuid' => $uuid, 'props' => $props));
    }
    
    
    static public function factory($class)
    {
    	try {
    			
    		$do = new self($class);
    
    	} catch (Exception $e) {
    			
    		throw new DataObject\Exception("PROPERTY_ERROR " . $e->getMessage());
    	}
    		
    	return $do;
    }
}
