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
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\ObjectModel;
use t41\ObjectModel\BaseObject;
use t41\ObjectModel\Property\CurrencyProperty;

/**
 * Meta property, value is calculated upon getValue() or getDisplayValue() call
 *
 * @category   t41
 * @package    t41\ObjectModel\Property
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MetaProperty extends AbstractProperty {

	
	public function getValue($param = null)
	{
		if ($this->_value != null) {
			return parent::getValue();
		}
		
		if (! $this->getParent()) {
			throw new Exception(sprintf("Meta property '%s' missing a parent reference", $this->_id));
		}
		
		$property = $this->getParent()->getRecursiveProperty($this->getParameter('property'));
		
		if ($property instanceof CollectionProperty) {
			$subParts = explode('.', $this->getParameter('action'));
			$collection = $property->getValue();
			return $collection->{$subParts[0]}(isset($subParts[1]) ? $subParts[1] : null);
			
		} else if ($property instanceof ObjectProperty) {
			$subParts = explode('.', $this->getParameter('action'));
			$object = $property->getValue(ObjectModel::MODEL);
			return $object instanceof BaseObject ? $object->{$subParts[0]}(isset($subParts[1]) ? $subParts[1] : null) : null;
				
		} else {
			$class = $this->_parent->getClass();
			$action = $this->getParameter('action');
			$array = array($this->_parent->getClass(), $this->getParameter('action'));
			try {
				$val = forward_static_call($array, $this->_parent);
			} catch (\Exception $e) {
				throw new Exception($e->getMessage());
			}
			
			return $val;
		}
	}
	
	
	public function getDisplayValue()
	{
		$value = $this->getValue();
		
		switch ($this->getParameter('type')) {
			
			case 'currency':
				$value = CurrencyProperty::format($value,2);
				break;
				
			case 'date':
			//	$value = DateProperty::format($value);
				break;
		}
		
		return $value;
	}
}
