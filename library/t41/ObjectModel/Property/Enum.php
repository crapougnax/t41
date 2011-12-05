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
 * @version    $Revision: 876 $
 */

/** Required files */
require_once 't41/Property/Abstract.php';

/**
 * Property class to use for enumeration of values
 *
 * @category   t41
 * @package    t41_Property
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class EnumProperty extends PropertyAbstract {

	
	protected $_values = array();
	
	
	public function getValues()
	{
		$lang = 'fr';
		
		if (! isset($this->_values[$lang])) {
			
			foreach ($this->getParameter('values') as $key => $value) {
			
				/* a generic label can be specified */ 
				if (isset($value['label'])) {
					
					$this->_values[$lang][$key] = $value['label'];
					
				} else if (isset($value[$lang])) {
					
					$this->_values[$lang][$key] = $value[$lang];
					
				} else {
					
					$this->_values[$lang][$key] = $value['en'];
				}
			}
		}
		
		return $this->_values[$lang];
	}
	
/*	
	public function getValue()
	{
		$values = $this->getValues();
		
		return isset($values[$this->_value]) ? $values[$this->_value] : null;
	} */
}