<?php

namespace t41\ObjectModel\Property;

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
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use	t41\Core;
use t41\Backend;
use t41\ObjectModel;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\BaseObject;
use t41\ObjectModel\Property;
use t41\ObjectModel\DataObject;

/**
 * Property class to use for object values
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectProperty extends AbstractProperty {

	
	protected $_displayValue;
	
	
	/**
	 * Set a value for the property
	 * 
	 * Value can be either:
	 * an instance of the object designated in the 'instanceof' parameter
	 * a data object if its getClass() method returns the same value as the 'instanceof' parameter
	 * a t41_Object_Uri instance (loosely validated)
	 * 
	 * @param object $value
	 */
	public function setValue($value)
	{
		if (is_string($value) && substr($value, 0,1) == Backend::PREFIX) {
			$value = new ObjectUri($value);
		}
		
		$instanceof = $this->getParameter('instanceof');
		if (! is_object($value) 
		   || (! $value instanceof $instanceof
		   		&& ! $value instanceof DataObject
		   		&& ! $value instanceof ObjectUri)) {
			
		   	$type = is_object($value) ? get_class($value) : gettype($value);
		   	throw new Exception(array("VALUE_MUST_BE_INSTANCEOF"
		   					  , array($this->getParameter('instanceof'), $value, $type, $this->_parent->getClass() . '::' . $this->_id)));
		}
		
		parent::setValue($value);
	}
	
	
	/**
	 * Return the current value in the $param form
	 * @see t41\ObjectModel\Property.AbstractProperty::getValue()
	 * @param string $param define which format to use
	 */
	public function getValue($param = null)
	{
		if (is_null($this->_value)) return null;

		/* if param is null, return the value in its current format */
		if (is_null($param)) return $this->_value;
		
		switch ($param) {
			
			case ObjectModel::MODEL:
				if ($this->_value instanceof ObjectModel\DataObject) {
					$this->_value = Core::_($this->_value->getUri());
					return $this->_value;
				} else if ($this->_value instanceof ObjectUri) {
					/* object uri */
					$this->_value = Core::_($this->_value);
				}
				return $this->_value;
				break;
				
			case ObjectModel::DATA:
				if ($this->_value instanceof ObjectUri) {
					$this->_value = Core::_($this->_value);
					return $this->_value->getDataObject();
				} else if ($this->_value instanceof DataObject) {
					return $this->_value;
				} else {
					return $this->_value->getDataObject();
				}
				break;

			case ObjectModel::URI:
			default:
				if ($this->_value instanceof ObjectUri) {
					return $this->_value;
				} else if ($this->_value instanceof DataObject) {
					return $this->_value->getUri();
				} else {
					return $this->_value->getDataObject()->getUri();
				}
				break;
		}
	}
	
	
	public function getDisplayValue()
	{
		if (empty($this->_displayValue) && $this->_value) {
			
			if (! $this->_value instanceof BaseObject) {
				$this->getValue(Property::OBJECT);
			}
			
			$this->_parseDisplayProperty();
		}
		
		return $this->_displayValue;
	}
	
	
	public function reduce(array $params = array(), $cache = true)
	{
		if (! $this->_value) {
			return parent::reduce($params, $cache);
		} else {
			// @todo improve performances !!
			$uuid = Core\Registry::set($this->getValue(ObjectModel::DATA));
			
			if (isset($params['extprops']) && ($params['extprops'] === true || array_key_exists($this->_id, (array) $params['extprops']))) {
				$value = $this->getValue(ObjectModel::DATA)->reduce(array('props' => $params['extprops'][$this->_id], 'extprops' => $params['extprops']), $cache);
			} else {
				$value = $this->getDisplayValue();
			}				
			return array_merge(parent::reduce($params, $cache), array('value' => $value, 'uuid' => $uuid));
		}
	}
	
	
	public function getInstanceOf()
	{
		return ObjectModel::factory($this->getParameter('instanceof'));
	}
}
