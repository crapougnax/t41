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

use t41\ObjectModel,
	t41\Core;

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
	
	
	public function setValueFromFile($file)
	{
		if (is_readable($file)) {
			$this->_value = file_get_contents($file);
			$this->_filename = $file;
		}
		return $this;
	}
	
	
	/**
	 * Return the current value in the $param form
	 * @see t41\ObjectModel\Property.AbstractProperty::getValue()
	 * @param string $param define which format to use
	 */
	public function getValue($param = null)
	{
		return $this->_value;
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
