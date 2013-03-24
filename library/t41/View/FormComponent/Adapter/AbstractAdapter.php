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
 * @version    $Revision: 865 $
 */

use t41\View;
use t41\ObjectModel;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\Property;
use t41\ObjectModel\Property\ObjectProperty;
use t41\View\FormComponent\Element\FieldElement;
use t41\ObjectModel\Property\AbstractProperty;

/**
 * t41 View Form Adapter Abstract class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractAdapter implements AdapterInterface {

	
	protected $_elements = array();
	
	
	protected $_buttons = array();
	
	
	protected $_data = array();
	
	
	protected $_errors = array();
	

	public function build(ObjectModel\DataObject $do, array $display = null, $identifier = false) {
		
		if ($identifier === true) {
			$identifier = new FieldElement(ObjectUri::IDENTIFIER);
			$identifier->setTitle("Identifiant unique")
						 ->setConstraint(Property::CONSTRAINT_MANDATORY, true)
						   ->setConstraint(Property::CONSTRAINT_MAXLENGTH, 10);
			$this->addElement($identifier);
		}
		
		if (is_null($display)) {
			$display = array_keys($do->getProperties());
		}
		
		foreach ($display as $element) {
			if (strpos($element,'.') !== false) {
				$parts = explode('.', $element);
				$tmprop = $do->getProperty($parts[0]);
				if ($tmprop instanceof ObjectProperty) {
					$property = $tmprop->getInstanceOf()->getProperty($parts[1]);
				} else {
					$property = $tmprop;
				}
			} else {
				$property = $do->getProperty($element);
			}
			
			if ($property instanceof AbstractProperty) {
				/* convert property to form element */
				$this->addElementFromProperty($property, $element, (count($this->_elements)+1) * 100);
			}
		}
	}
		
	
	public function validate()
	{
		return true;
	}
	
	
	public function addElementFromProperty(Property\AbstractProperty $property, $position = null)
	{
		throw new View\Exception("You need to redeclare this function in your adapter to use it");
	}
	
	
	public function addElement($element, $position = null)
	{
		/* give the element a reference to its parent */
//		$element->setParent($this);
		
		/* @todo $element should not be required to have a getId() function */ 
		$this->_elements[$element->getId()]  = $element;
		// @todo find a way to store fields positions // new t41_Position($position));
		return $this;
	}
	
	
	public function getElements()
	{
		return $this->_elements;
	}
	
	
	public function getElement($key)
	{
		return (isset($this->_elements[$key])) ? $this->_elements[$key] : false; 
	}
	

	public function getButton($key)
	{
		return (isset($this->_buttons[$key])) ? $this->_buttons[$key] : false; 
	}
	
	
	public function getErrors()
	{
		return $this->_errors;
	}
	
	
	public function position($key)
	{
		return isset($this->_positions[$key]) ? $this->_positions[$key] : false;
	}
	
	
	public function keepOnly($var)
	{
		if (! is_array($var)) $var = (array) $var;
		
		foreach ($this->_elements as $key => $element) {
			
			if (! in_array($element->getId(), $var)) {
				
				unset($this->_elements[$key]);
			}
		}
		return $this;
	}
}
