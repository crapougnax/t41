<?php

namespace t41\View\FormComponent\Adapter;

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
use	t41\ObjectModel\Property;
use	t41\View\FormComponent\Element;
use	t41\View\Exception;
use t41\ObjectModel\Property\AbstractProperty;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class DefaultAdapter extends AbstractAdapter {

	
	static public $constraintsList = array(Property::CONSTRAINT_MANDATORY
										 , Property::CONSTRAINT_UNIQUE
										 , Property::CONSTRAINT_PROTECTED
										 , Property::CONSTRAINT_ENCRYPTED
										 , Property::CONSTRAINT_EMAILADDRESS
										 , Property::CONSTRAINT_URLSCHEME
										 , Property::CONSTRAINT_MINLENGTH
										 , Property::CONSTRAINT_MAXLENGTH
										 , Property::CONSTRAINT_HASDIGITS
										 , Property::CONSTRAINT_HASLETTERS
										 , Property::CONSTRAINT_UPPERCASE
										 , Property::CONSTRAINT_LOWERCASE
										// , Property::CONSTRAINT_MULTIPLE
										 , Property::CONSTRAINT_DATEMIN
										 , Property::CONSTRAINT_DATEMAX
										 , Property::CONSTRAINT_HOURMIN
										 , Property::CONSTRAINT_HOURMAX
										 , Property::CONSTRAINT_MINUTERANGE
										 , Property::CONSTRAINT_MAXSIZE
										);

	
	public function addElementFromProperty(AbstractProperty $property, $fname, $position = null)
	{
		$class = get_class($property);
		$class = substr($class, strrpos($class, '\\')+1);
		
		switch ($class) {
			
			case 'EnumProperty':
				if ($property->getParameter('constraints.multiple') !== false) {
					$element = new Element\MultipleElement();
				} else {
					$element = new Element\EnumElement();
				}
				$element->setEnumValues($property->getValues());
				break;
				
			case 'DateProperty':
				$element = new Element\DateElement();
				break;

			case 'TimeProperty':
				$element = new Element\TimeElement();
				break;
									
			case 'CurrencyProperty':
				$element = new Element\CurrencyElement();
				break;

			case 'StringProperty':
				if ($property->getParameter('multilines')) {
					$element = new Element\TextElement();
				} else {
					$element = new Element\FieldElement();
				}
				break;
					
			case 'ObjectProperty':
				// @todo join this code and the one in CollectionProperty::getValue
				if ($property->getParameter('instanceof') == 't41\ObjectModel\MediaObject') {
					$element = new Element\MediaElement();
				} else {
					$element = new Element\ListElement();
					$element->setParameter('display', $property->getParameter('display'));
				
					$collection = new ObjectModel\Collection($property->getParameter('instanceof'));
				
					/* inject the condition that allows to find collection members */
					if ($property->getParameter('keyprop')) {
						$collection->having($property->getParameter('keyprop'))->equals($property->getParent());
					}
				
					if ($property->getParameter('depends')) {
						$element->setParameter('dependency',$property->getParameter('depends'));
					}
				
					if ($property->getParameter('morekeyprop')) {
						foreach ($property->getParameter('morekeyprop') as $value) {
							if (strstr($value, ' ') !== false) {
				
								// if value contains spaces, it's a pattern
								$parts = explode(' ', $value);
								if (count($parts) == 3) {
									if (strstr($parts[2],',') !== false) $parts[2] = explode(',', $parts[2]);
									$collection->having($parts[0])->$parts[1]($parts[2]);
								} else {
									if (strstr($parts[1],',') !== false) $parts[1] = explode(',', $parts[1]);
									$collection->having($parts[0])->equals($parts[1]);
								}
							} else {
								// default case, we expect the member to hold a property
								// with the same name and value as the current object
								$collection->having($value)->equals($property->getParent()->getProperty($value)->getValue());
							}
						}
					}
				
					if ($property->getParameter('sorting')) {
						$element->setParameter('sorting', $property->getParameter('sorting'));
					}
				
					if ($property->getParameter('search')) {
						$element->setParameter('search', $property->getParameter('search'));
					}

					$element->setCollection($collection);
				}
				break;
			
			case 'CollectionProperty':
				$element = new Element\GridElement();
				$element->setCollection($property->getValue());
				break;
				
			case 'MediaProperty':
				$element = new Element\MediaElement();
				break;
					
				
			default:
				$element = new Element\FieldElement();
				break;

		}
		
		$element->setId(str_replace('.','-', $fname));
		$element->setTitle($property->getLabel());
		$element->setDefaultValue($property->getDefaultValue());
		$element->setHelp($property->getParameter('help'));
		
		$value = $property->getValue();
		if ($value instanceof ObjectModel\ObjectUri) {
			$value = $value->__toString();
		} else if ($value instanceof ObjectModel\BaseObject) {
			$value = $value->getUri() ? $value->getUri()->__toString() : null;
		}
		$element->setValue($value);
		
		$constraints = $property->getParameter('constraints');
		foreach (self::$constraintsList as $key) {
			if (isset($constraints[$key])) {
				$element->setConstraint($key, $constraints[$key] != '0' && empty($constraints[$key]) ? true : $constraints[$key]);
			}
		}
		
		// property uses a special format for which we should have a decorator
		if (isset($constraints['format'])) {
			$element->setDecorator($constraints['format']);
		}
		
		return $this->addElement($element, $position);
	}
	
	
	public function addElement($element, $position = null)
	{
		if (! $element instanceof Element\AbstractElement) {
			throw new Exception(array("OBJECT_NOT_INSTANCE_OF", 't41\View\FormComponent\Element\AbstractElement'));
		}
		return parent::addElement($element, $position);
	}
	
	
	public function validate()
	{
		$this->_errors = array();
		
		/* @var $element t41_View_Form_Element_Abstract */
		foreach ($this->_elements as $element) {
			$value = $this->_data[$element->getId()];
			/* test each form element for data consistency */
			if ($element) {
			}
		}
	}
}
