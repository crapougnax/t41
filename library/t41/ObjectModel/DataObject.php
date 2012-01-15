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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */


/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DataObject {
	
	/**
	 * Object URI where data can be found
	 *
	 * @var t41_Object_Uri
	 */
	protected $_uri;
	
	/**
	 * Name of class related to data object 
	 *
	 * @var string
	 */
	protected $_class;
	
	/**
	 * Array of t41_Property_Abstract objects
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
    public function setUri(Uri $uri)
    {
    	$this->_uri = $uri;
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
    	
    	if (\t41\ObjectModel::getObjectExtends($var)) {

    		$properties = \t41\ObjectModel::getObjectProperties(ObjectModel::getObjectExtends($var));
    		
    	} else {
    		
    		$properties = array();
    	}
    	
    	$properties += (array) \t41\ObjectModel::getObjectProperties($var);
    	
    	if ($properties !== false) {
    		
    		foreach ($properties as $propertyId => $propertyParams) {
    			
    			$this->_data[$propertyId] = Property::factory($propertyId, $propertyParams['type'], $propertyParams);
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
	 * Retourne un tableau contenant les données du Data Object
	 * 
	 * @param t41_Backend_Adapter_Abstract $backend
	 * @todo remove parameter whicch value is already available in object uri
	 * @return array
	 */
    public function toArray(\t41\Backend\Adapter\AdapterAbstract $backend = null)
    {
    	if (is_null($backend)) $backend = \t41\ObjectModel::getObjectBackend($this->_class);
    	
    	$result = array('data' => array(), 'collections' => array());
    	
    	/* @var $value t41_Property_Abstract */
    	foreach ($this->_data as $key => $value) {
    		
    		if ($value instanceof Property\Collection) {
    			
    			if ($value->getParameter('embedded') == true) {
    				
    				$array = array();
    				
    				/* @var $member t41_Object_Model */
    				foreach ($value->getValue()->getMembers() as $member) {
    					
    					$array[] = $member->getDataObject()->toArray();
    				}
    				$result['data'][$key] = $array;
    			
    			} else {
    			
    				// this property is not part of the saved data set
    				$result['collection'][$key] = $value;
    				continue;
    			}
    		}
    		
    		if ($value instanceof t41_Property_Object) {
    			
    			$value = $value->getValue();
    			$doBackend = ($this->_uri instanceof t41_Object_Uri) ? $this->_uri->getBackendUri()->getAlias() : null;
    			
    			if ($value instanceof t41_Object_Model) {
    				
    				$value = $value->getUri();
    				
	    			/* check backends if they're identical, just keep identifier value*/
    				if ($value->getBackendUri()->getAlias() == $doBackend) { //$backend->getUri()->getAlias()) {
    					
    					$value = $value->getIdentifier();
    				}
    			} else if ($value instanceof t41_Object_Uri) {
    				
    				 /* check backends if they're identical, just keep identifier value*/
    				if ($value->getBackendUri()->getAlias() == $doBackend) { //$backend->getUri()->getAlias()) {
    					
    					$value = $value->getIdentifier();
    				}
    			}
    			
    			$result['data'][$key] = $value;
    			
    		} else if ($value instanceof t41_Property_Abstract){

    			$value = $value->getValue();
    			$result['data'][$key] = ($value instanceof t41_Object_Model) ? $value->getUri() : $value;
    			
    		} else {

    			$result['data'][$key] = $value;
    		}
    	}
    	
    	return $result;
    }
    
    

    /**
     * Returns the Property object associated with the given key
     * @param string $name
     * @return t41_Property_Abstract
     */
    public function getProperty($name)
    {
    	if (strpos($name, '.') === false) {
    		
    		return (isset($this->_data[$name])) ? /*clone*/ $this->_data[$name] : false; 
    	}
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
     * Returns an array of t41_Form_Elements_* based on properties
     * 
     * @todo lots of improvements!
     * 
     * @return array
     */
    public function getPropertiesAsElements()
    {
    	$array = array();
    	
    	foreach ($this->_data as $key => $val) {
    		
    		$element = new t41_Form_Element_Generic();
			$element->setId($key);
			$element->setLabel($val->getLabel() ? $val->getLabel() : $key);
			$element->setValue($val->getValue());    	
    		
    		$array[$key] = $element;
    	}
    	
    	return $array;
    }
    
    
    /**
     * Populates a data object
     *
     * @param array $data
     * @param t41_Backend_Mapper $mapper
     * @return t41_Data_Object
     */
    public function populate(array $data, t41_Backend_Mapper $mapper = null)
    {
    	if ($mapper) {
    		
    		$data = $mapper->toDataObject($data, $this->_class);
    	}
    	
    	// then sent to data object properties
    	foreach ($data as $key => $value) {
    		
    		if (isset($this->_data[$key]) && ! empty($value)) {

    			/* @var $property t41_Property_Abstract */
    			$property = $this->_data[$key];
    			
    			if ($property instanceof t41_Property_Object) {

    				if (substr($value, 0, 1) == t41_Backend::PREFIX) {
    					
		    			$property->setValue(new t41_Object_Uri($value));
    					continue;
    				}
    				
    				$backend = t41_Object::getObjectBackend($property->getParameter('instanceof'));
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
    	
    	return $this;
    }
    
    
    /**
     * Map properties against mapper to obtain a backend-compatible array
     * 
     * @param t41_Backend_Mapper $mapper
     * @param t41_Backend_Adapter_Abstract $backend
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
}