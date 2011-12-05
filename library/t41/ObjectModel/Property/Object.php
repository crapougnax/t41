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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */

/** Required files */
require_once 't41/Property/Abstract.php';

/**
 * Property class to use for object values
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectProperty extends PropertyAbstract {

	
	const UNDEFINED_LABEL	= "Undefined Label";
	
	
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
		if (! is_object($value) 
		   || (get_class($value) != $this->getParameter('instanceof')
		   && ! $value instanceof t41_Data_Object
		   && ! $value instanceof t41_Object_Uri)) {
			
		   	$type = is_object($value) ? get_class($value) : gettype($value);
		   	throw new t41_Property_Exception(array("VALUE_MUST_BE_INSTANCEOF", array($this->getParameter('instanceof'), $type)));
		}
		
		parent::setValue($value);
	}
	
	
	public function getValue($param = null)
	{
		if (is_null($this->_value)) return null;
		
		switch ($param) {
			
			case t41_Property::OBJECT:
				if ($this->_value instanceof t41_Data_Object) {
					
					$this->_value = t41_Object::factory($this->_value);
					return $this->_value;
					
				} else if ($this->_value instanceof t41_Object_Uri) {
					/* object uri */
					$this->_value = t41_Object::factory($this->_value);
				}
				return $this->_value;
			break;
				
			case t41_Property::DATA:
				if ($this->_value instanceof t41_Object_Uri) {
					
					$this->_value = t41_Object::factory($this->_value);
					return $this->_value->getDataObject();
					
				} else if ($this->_value instanceof t41_Data_Object) {
					
					return $this->_value;
					
				} else {
					
					return $this->_value->getDataObject();
				}
				break;

			case t41_Property::URI:
			default:
				if ($this->_value instanceof t41_Object_Uri) {
					
					return $this->_value;
					
				} else if ($this->_value instanceof t41_Data_Object) {
					
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
			
			if (! $this->_value instanceof t41_Object_Model) {
				
				$this->getValue(t41_Property::OBJECT);
			}
			
			$displayProps = explode(',', $this->getParameter('display'));
			if (count($displayProps) == 1 && $displayProps[0] == '') {
				
				$this->_displayValue = self::UNDEFINED_LABEL;
				
			} else {

				$this->_displayValue = array();
        		foreach ($displayProps as $disProp) {

            		$this->_displayValue[] = $this->_value->getProperty($disProp);
            	}
            
            	$this->_displayValue = implode(' ', $this->_displayValue);
			}
		}
		
		return $this->_displayValue;
	}
}