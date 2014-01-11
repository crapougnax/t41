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
 * Class for a Time Property
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class TimeProperty extends AbstractProperty {

	
	const NOW	= '_NOW_';
	
	const HOUR_PART	= 'H';
	
	const MIN_PART = 'M';
	
	const SEC_PART = 'S';
	
	
	public function setValue($value)
	{
		switch ($value) {
			
			case self::NOW:
				$value = date('H:i');
				break;
				
			default:
				break;
		}
		
		parent::setValue($value);
	}
	
	
	public function getValue($part = null)
	{
		if (is_null($part)) {
			return $this->_value;
		}
		$parts = explode(':', $this->_value);
		
		switch ($part) {
			case self::HOUR_PART:
				return $parts[0];
				break;
		
			case self::MIN_PART:
				return $parts[1];
				break;
		
			case self::SEC_PART:
				return isset($parts[2]) ? $parts[2] : '00';
				break;
				
			default:
				return $this->_value;
		}	
	}
	
	
	public function getDisplayValue()
	{
		return self::format($this->_value);
	}
	
	
	
	public function getEnumValues($part)
	{
		die('coucou');
		$array = array();
		\Zend_Debug::dump($part); die;
		
		switch ($part) {
			
			case self::HOUR_PART:
				$min = $this->getParameter('constraints.hourmin') ? $this->getParameter('constraints.hourmin') : 0;
				$max = $this->getParameter('constraints.hourmax') ? $this->getParameter('constraints.hourmax') : 23;
				for ($i = $min ; $i <= $max ; $i++) {
					$array[$i] = str_pad($i, 2, '0');
				}
				break;
				
			case self::MIN_PART:
				for ($i = 0 ; $i < 60 ; $i++) {
					$array[$i] = str_pad($i, 2, '0');
				}
				break;
				
			case self::SEC_PART:
				for ($i = 0 ; $i < 60 ; $i++) {
					$array[$i] = str_pad($i, 2, '0');
				}
				break;
		}

		return $array;
	}
	
	
	static public function format($str)
	{
		if (is_null($str) || $str == '') {
			return '';
		}
		$hour = explode(':', $str);
		return sprintf(' %sh%s', $hour[0], isset($hour[1]) ? str_pad($hour[1], 2, STR_PAD_LEFT) : '00');
	}
}
