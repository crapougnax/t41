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
 * @version    $Revision: 856 $
 */


/**
 * Class for an Array Property
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ArrayProperty extends AbstractProperty {

	
	public function setValue($value)
	{
		// try to detect serialized values
		if (@unserialize($value) !== false) {
			$value = unserialize($value);
		}
		
		if (! is_array($value)) {
			throw new Exception("This property accepts only arrays");
		}
		parent::setValue($value);
	}
	
	
	public function getValue($key = null)
	{
		if (is_null($key)) {
			return parent::getValue();
		} else {
			if (isset($this->_value[$key])) {
				return $this->_value[$key];
			} else {
				return false;
			}
		}
	}
}
