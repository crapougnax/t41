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

use t41\View\Action\ObjectAction;

use t41\ObjectModel,
	t41\ObjectModel\Property;

/**
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class SimpleRule extends AbstractRule {
	
	
	/**
	 * Execute the given method or function
	 * 
	 *  @param t41\ObjectModel\Property\AbstractProperty $obj
	 *  @return boolean
	 */
	public function execute(Property\AbstractProperty $property)
	{
		$do = $this->_object->getDataObject();
		
		try {
			
			$source = $this->_source->getValue();
			
			/* get source value */
			if (! $this->_source->isMethod()) {
	

				throw new Exception("Source must be a method or a function");
			}

			// recursion
			if (strstr($source, '.') !== false) {

				$parts = explode('.', $source);
				foreach ($parts as $part) {
						
					$property = $do->getProperty($part);
						
					if ($property instanceof Property\AbstractProperty) {
							
						if ($property->getValue() instanceof ObjectModel\ObjectModelAbstract){
								
							$obj = $property->getValue();
						}
							
					} else {
								
						$source = $part;
					}
				}
			} else {
					
				$obj = $this->_object;
			}

			/**
			 * IMPORTANT: if $obj is a collection from an unsaved object
			 * 			  don't execute rule because there is no uri yet to bind members to object
			 * @todo improve this kind of detection (a switch on the collection or the property itself ?)
			 */
				
			if ($obj instanceof ObjectModel\Collection && $this->_object->getUri() == null) {
					
				return;
			}
				
			$value = $obj->$source($this->_source->getArgument());
			
		} catch (Exception $e) {
			
			/* @todo log exception */
			$this->_object->setStatus('Exception executing rule: ' . $e->getMessage(), 0, array('rule' => __CLASS____));
			
			return false;
		}
		
		return true;
	}
}
