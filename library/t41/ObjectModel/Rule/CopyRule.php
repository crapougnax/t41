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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

/**
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class CopyRule extends RuleAbstract {
	
	
	/**
	 * Copy value or method return of source property to destination property
	 * 
	 *  @param t41_Data_Object $do
	 *  @return boolean
	 */
	public function execute(\t41\ObjectModel\DataObject $do)
	{
		try {
			/* get source value */
			if (is_array($this->_source)) {
	
				$value = $do->getProperty($this->_source[0])->{$this->_source[1]}();
			
			} else {
			
				$value = $do->getProperty($this->_source)->getValue();
			}
			
			var_dump($this->_source);
		
			/* set destination value with source value */
			if (is_array($this->_destination)) {
			
				$do->getProperty($this->_destination[0])->getProperty($this->_destination[1])->setValue($value);
			
			} else {
			
				$do->getProperty($this->_destination)->setValue($value);
			}		
		} catch (Exception $e) {
			
			/* @todo log exception */
			return false;
		}
		
		return true;
	}
}