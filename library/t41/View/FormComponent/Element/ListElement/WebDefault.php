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
		if ($this->_obj->getTotalValues() > $this->_obj->getParameter('select_max_values') 
				&& $this->getParameter('mode') != View\FormComponent::SEARCH_MODE) {

			View::addCoreLib(array('core.js','locale.js','view.js','view:table.js','view:action:autocomplete.js'));
			$acfield = new View\FormComponent\Element\FieldElement('_' . $name);
			//$acfield->setValue($this->_obj->getValue()->getDisplayValue());
			
			$action = new AutocompleteAction($this->_obj->getCollection());
			$action->setParameter('search', array('label'));
			$action->setParameter('display', array('type','label'));
			$action->setParameter('event', 'keyup');
			$action->setContextData('onclick', 't41.view.element.autocomplete.close');
			$action->setContextData('target', $this->_nametoDomId($name)); //$this->_obj->getId());
			$action->bind($acfield);
			
			$deco = View\Decorator::factory($acfield);
			$html = $deco->render();
			
			$deco = View\Decorator::factory($action);
			$deco->render();
			
			$html .= sprintf('<input type="hidden" name="%s" id="%s" value="%s"/>', $name, $this->_nametoDomId($name), $value);
			return $html . "\n";
			
		} else {
			// display menu list
			$zv = new \Zend_View();
			$options = array(null => $this->getParameter('defaultlabel')) + (array) $this->_obj->getEnumValues();
			return $zv->formSelect($name, $value, null, $options);
		}
	}
}
