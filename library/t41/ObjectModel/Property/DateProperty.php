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

use t41\ObjectModel\Property\Exception;

/**
 * Class for a Date Property
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DateProperty extends AbstractProperty {

	
	const TODAY		= 'TODAY';
	
	const TODAY_DATE	= 'STODAY';

	const TODAY_TIME	= 'TTODAY';

	public function setValue($value)
	{
		switch ($value) {
			
			case self::TODAY:
				$value = date('Y-m-d H:i:s');
				break;

			case self::TODAY_DATE:
				$value = date('Y-m-d');
				break;
				
			case self::TODAY_TIME:
				$value = date('H:i:s');
				break;
					
			default:
				if ($this->getParameter('format') && ! \Zend_Date::isDate($value, $this->getParameter('format'))) {
					throw new Exception(array("VALUE_NOT_A_DATE", array($this->_id, $value)));
				}
				break;
		}
		
		parent::setValue($value);
	}
	
	
	
	public function getDisplayValue()
	{
		return self::format($this->_value);
	}
	
	
	static public function format($str)
	{
		$parts = explode(' ', $str);
			
		$date = explode('-', $parts[0]);
		$date = array_reverse($date);
		$date = implode('/', $date);
		
		if (isset($parts[1])) {
			$hour = explode(':',$parts[1]);
			$date .= sprintf(' %dh%d', $hour[0], $hour[1]);
		}
		return $date;
	}
}
