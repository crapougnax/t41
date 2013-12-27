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
 * @version    $Revision: 865 $
 */

use t41\ObjectModel;
use t41\Core;

/**
 * Property class to use for object values
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MediaProperty extends AbstractProperty {

	
	const TMP_PREFIX = 'tmp:';
	
	protected $_displayValue;
	
	protected $_filename;
	
	
	/**
	 * Set a value for the property
	 * 
	 * Value can be either:
	 * the full path to a file
	 * the binary content of the file
	 * 
	 * @param string $value
	 */
	public function setValue($value)
	{
		// @todo implement constraints
		
		if (substr($value, 0, 1) == DIRECTORY_SEPARATOR) {
			return $this->setValueFromFile($value);
		} else {
			return parent::setValue($value);
		}
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
					$this->_value = ObjectModel::factory($this->_value);
					return $this->_value;
				} else if ($this->_value instanceof ObjectUri) {
					/* object uri */
					$this->_value = ObjectModel::factory($this->_value);
				}
				return $this->_value;
				break;
	
			case ObjectModel::DATA:
				if ($this->_value instanceof ObjectUri) {
					$this->_value = ObjectModel::factory($this->_value);
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
		return array('uri' => $this->_parent->getUri(), 'property' => $this->_id);
	}
	
	
	public function reduce(array $params = array(), $cache = true)
	{
		return array();
	}
}
