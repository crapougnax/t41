<?php

namespace t41\View\FormComponent\Element\ListElement;

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

use t41\ObjectModel,
	t41\View,
	t41\View\ViewUri,
	t41\View\FormComponent,
	t41\View\Action\AutocompleteAction,
	t41\View\Decorator\AbstractWebDecorator;

/**
 * t41 default web decorator for list elements
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		if (($value = $this->_obj->getValue()) !== false) {
			if (($value instanceof ObjectModel\BaseObject || $value instanceof ObjectModel\DataObject) && $value->getUri()) {
				$value = $this->_obj->getParameter('altkey') ? $value->getProperty($this->_obj->getParameter('altkey'))->getValue() : $value->getUri()->getIdentifier();
			} else if ($value instanceof ObjectModel\ObjectUri) {
				$value = $value->getIdentifier();
			} else if ($this->_obj->getDefaultValue()) {
				$value = is_object($this->_obj->getDefaultValue()) ? $this->_obj->getDefaultValue()->getIdentifier() : $this->_obj->getDefaultValue();
			} else {
				$value = null;
			}
		}
		
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
		}
		
		// display autocompleter field
		if ($this->_obj->getTotalValues() > $this->_obj->getParameter('selectmax') 
				&& $this->getParameter('mode') != View\FormComponent::SEARCH_MODE) {

			$deco = new WebAutocomplete($this->_obj, array($this->_params));
			return $deco->render();
			
		} else {
			// display menu list
			$zv = new \Zend_View();
			$options = array(null => $this->getParameter('defaultlabel')) + (array) $this->_obj->getEnumValues();
			return $zv->formSelect($name, $value, null, $options);
		}
	}
}
