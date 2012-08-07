<?php

namespace t41\View\ListComponent\Element;

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

use t41\ObjectModel\Property\AbstractProperty;

use t41\ObjectModel\Property\CurrencyProperty;

use t41\ObjectModel\Property\CollectionProperty;

use t41\ObjectModel\DataObject;

use t41\View\ListComponent\Element\AbstractElement;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MetaElement extends AbstractElement {

	
	public function getValue(DataObject $do)
	{
		$property = $do->getProperty($this->getParameter('property'));
			
		if ($property instanceof CollectionProperty) {
				
			$subParts = explode('.', $this->getParameter('action'));
			$collection = $property->getValue();
			return $collection->{$subParts[0]}(isset($subParts[1]) ? $subParts[1] : null);
			
		} else if ($property instanceof AbstractProperty) {
			
			return $property->getValue();
			
		} else {
			
			return $this->_value; //parent::getValue();
		}
	}
	
	
	public function getDisplayValue(DataObject $do)
	{
		$value = $this->getValue($do);
		
		switch ($this->getParameter('type')) {
			
			case 'currency':
				$value = CurrencyProperty::format($value);
				break;
		}
		
		return $value;
	}
}
