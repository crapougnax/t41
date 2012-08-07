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
 * @version    $Revision: 832 $
 */

/**
 * Class for a Date Property
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DateProperty extends AbstractProperty {

/*	
	public function setValue($value)
	{
		Zend_Loader::loadClass('Zend_Date');
		
		if (! Zend_Date::isDate($value, $this->getParameter('format'))) {
			
			throw new t41_Property_Exception(array("VALUE_NOT_A_DATE", array($this->_id, $value)));
		}
		
		parent::setValue(new Zend_Date($value));
	}
	*/
	
	public function getDisplayValue()
	{
		return self::format($this->_value);
	}
	
	
	static public function format($str)
	{
		$parts = explode('-', $str);
		$parts = array_reverse($parts);
		return implode('/', $parts);
	}
}
