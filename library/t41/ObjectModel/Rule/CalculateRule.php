<?php

namespace t41\ObjectModel\Rule;

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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\ObjectModel;
use t41\ObjectModel\Property;

/**
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class CalculateRule extends RuleAbstract {
	
	
	public function setSource($str)
	{
		/*
		 * Supported formulas are pretty basic: each element must separated from other by one space 
		 */
		
		/* @todo write/use a more complex formula parser */
		$elem = explode(' ', $str);
		
		$this->_source = $elem;
		
		return $this;
	}
	
	/**
	 * Copy value or method return of source property to destination property
	 * 
	 *  @param t41_Data_Object $do
	 *  @return boolean
	 */
	public function execute(t41_Data_Object $do)
	{
		try {
			
			$prep = array();
			
			foreach ($this->_source as $elem) {
				
				if (in_array($elem, array('+', '-', '/', '*'))) {
					
					$prep[] = $elem;
					
				} else {
					
					if (strpos($elem, '.') !== false) {

						$props = explode('.', $elem);
						$value = $do;
						foreach ($props as $prop) {

							if ($value instanceof \t41\ObjectModel\DataObject) { 
								
								$value = $value->getProperty($prop)->getValue();

							} else if ($value instanceof ObjectModel\BaseObject) {
								
								$value = $value->getProperty($prop);
							
							} else if ($value instanceof Property\PropertyAbstract) {
								
								$value = $value->getValue();
							}
						}
						
						$prep[] = ($value instanceof Property\PropertyAbstract) ? $value->getValue() : $value;
			
					} else {
			
						$prep[] = $do->getProperty($elem)->getValue();
					}					
				}
			}

			$value = (float) eval(sprintf('return %s;', implode($prep)));

			/* set destination value with source value */
			if (is_array($this->_destination)) {
			
				$do->getProperty($this->_destination[0])->getProperty($this->_destination[1])->setValue($value);
			
			} else {
			
				$do->getProperty($this->_destination)->setValue($value);
			}
			
		} catch (Exception $e) {
			
			echo $e->getTraceAsString();
			/* @todo log exception */
			return false;
		}
		
		return true;
	}
}
